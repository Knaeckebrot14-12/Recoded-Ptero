<?php

namespace Pterodactyl\Services\Files;

use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;
use Pterodactyl\Models\ServerTrashItem;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

/**
 * The file manager's trash: deleted files and folders are moved into the server's .trash folder
 * (one sub folder per deletion) and can be restored for config('mcpanel.trash.hours') hours.
 * After that p:files:purge-trash deletes them for good. Files deleted over SFTP or by the game
 * server itself are not covered.
 */
class TrashService
{
    public const DIRECTORY = '.trash';

    public function __construct(private DaemonFileRepository $files, private DaemonServerRepository $daemon)
    {
    }

    public static function hours(): int
    {
        return max(1, (int) config('mcpanel.trash.hours', 24));
    }

    /**
     * "a/./b/../c/" -> "a/c" (like the paths Wings works with, never above the server's root).
     */
    public static function normalize(?string $path): string
    {
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', (string) $path)) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    public static function isInTrash(string $normalized): bool
    {
        return $normalized === self::DIRECTORY || str_starts_with($normalized, self::DIRECTORY . '/');
    }

    /**
     * Moves the files into the trash. Files inside the trash itself (and the trash folder) are
     * deleted for good. Returns the number of files and folders moved into the trash.
     */
    public function trash(Server $server, ?string $root, array $files, ?User $user): int
    {
        $root = self::normalize($root);
        $names = array_values(array_unique(array_filter(array_map(fn ($f) => self::normalize($f), $files), fn ($f) => $f !== '')));

        $permanent = [];
        $move = [];
        foreach ($names as $name) {
            $full = self::normalize($root . '/' . $name);
            if ($full === '' || self::isInTrash($full)) {
                $permanent[] = $name;
            } else {
                $move[] = $name;
            }
        }

        if ($permanent !== []) {
            $this->deletePermanently($server, $root, $permanent);
        }
        if ($move === []) {
            return 0;
        }

        $batch = now()->timestamp . '-' . Str::lower(Str::random(6));
        $renames = [];
        $rows = [];
        $listings = [];
        $used = [];
        foreach ($move as $name) {
            $full = self::normalize($root . '/' . $name);
            $parent = self::normalize(dirname('/' . $full));

            // What exists (and how big it is): files that are already gone are simply skipped.
            $listings[$parent] ??= collect($this->files->setServer($server)->getDirectory($parent === '' ? '/' : $parent))->keyBy('name');
            $entry = $listings[$parent]->get(basename($full));
            if (!$entry) {
                continue;
            }

            // Names from different folders can be the same; each gets its own place in the batch.
            $base = basename($full);
            $slot = $base;
            for ($i = 2; isset($used[$slot]); ++$i) {
                $slot = $i . '-' . $base;
            }
            $used[$slot] = true;
            $trashPath = self::DIRECTORY . '/' . $batch . '/' . $slot;
            $renames[] = ['from' => $full, 'to' => $trashPath];
            $rows[] = [
                'server_id' => $server->id,
                'user_id' => $user?->id,
                'kind' => ServerTrashItem::KIND_FILE,
                'batch' => $batch,
                'original_path' => '/' . $full,
                'trash_path' => $trashPath,
                'is_file' => (bool) ($entry['file'] ?? true),
                'size' => (int) ($entry['size'] ?? 0),
                'created_at' => now(),
            ];
        }

        if ($renames === []) {
            return 0;
        }

        $this->files->setServer($server)->renameFiles('/', $renames);
        ServerTrashItem::query()->insert($rows);

        return count($rows);
    }

    /**
     * Deletes files for good, and forgets trash entries that were inside them.
     */
    public function deletePermanently(Server $server, string $root, array $names): void
    {
        $this->files->setServer($server)->deleteFiles($root === '' ? '/' : $root, $names);

        foreach ($names as $name) {
            $full = self::normalize($root . '/' . $name);
            if ($full === self::DIRECTORY) {
                ServerTrashItem::query()->where('server_id', $server->id)->delete();
            } elseif (self::isInTrash($full)) {
                ServerTrashItem::query()->where('server_id', $server->id)
                    ->where(fn ($q) => $q->where('trash_path', $full)->orWhere('trash_path', 'like', addcslashes($full, '%_\\') . '/%'))
                    ->delete();
            }
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, ServerTrashItem>
     */
    public function items(Server $server): Collection
    {
        return ServerTrashItem::query()->where('server_id', $server->id)->orderByDesc('created_at')->orderByDesc('id')->get();
    }

    /**
     * Puts entries back where they were. A file or folder that exists there again is restored
     * next to it as "name (restored)". Returns the paths they were restored to.
     */
    public function restore(Server $server, array $ids): array
    {
        $items = ServerTrashItem::query()->where('server_id', $server->id)->whereIn('id', $ids)->get();
        $restored = [];
        $listings = [];

        foreach ($items as $item) {
            if ($item->kind === ServerTrashItem::KIND_CLONE) {
                $this->undoClone($server, $item);
                $restored[] = '/';
                continue;
            }

            $original = self::normalize($item->original_path);
            $parent = self::normalize(dirname('/' . $original));
            if (!array_key_exists($parent, $listings)) {
                try {
                    $listings[$parent] = collect($this->files->setServer($server)->getDirectory($parent === '' ? '/' : $parent))->pluck('name')->all();
                } catch (DaemonConnectionException $exception) {
                    // A folder that no longer exists is created again by the move.
                    if ($exception->getStatusCode() !== 404) {
                        throw $exception;
                    }
                    $listings[$parent] = [];
                }
            }

            $name = $this->freeName(basename($original), $listings[$parent], $item->is_file);
            $target = ltrim($parent . '/' . $name, '/');
            $this->files->setServer($server)->renameFiles('/', [['from' => $item->trash_path, 'to' => $target]]);
            $listings[$parent][] = $name;
            $restored[] = '/' . $target;

            $item->delete();
            $this->removeBatchIfEmpty($server, $item->batch);
        }

        return $restored;
    }

    /**
     * Deletes entries for good (all of the server's when $ids is null).
     */
    public function purge(Server $server, ?array $ids): int
    {
        $query = ServerTrashItem::query()->where('server_id', $server->id);
        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }
        $items = $query->get();
        if ($items->isEmpty()) {
            return 0;
        }

        foreach ($items->groupBy('batch') as $batch => $group) {
            $remaining = ServerTrashItem::query()->where('server_id', $server->id)->where('batch', $batch)->whereNotIn('id', $group->pluck('id'))->exists();
            if ($remaining) {
                $this->files->setServer($server)->deleteFiles(self::DIRECTORY . '/' . $batch, $group->map(fn ($i) => basename($i->trash_path))->values()->all());
            } else {
                $this->files->setServer($server)->deleteFiles(self::DIRECTORY, [$batch]);
            }
            ServerTrashItem::query()->whereIn('id', $group->pluck('id'))->delete();
        }

        return $items->count();
    }

    /**
     * Deletes everything that has been in the trash longer than the configured time. A node that
     * can't be reached is tried again on the next run.
     */
    public function purgeExpired(): int
    {
        $count = 0;
        $expired = ServerTrashItem::query()->with('server.node')->where('created_at', '<', now()->subHours(self::hours()))->get();

        foreach ($expired->groupBy(fn ($i) => $i->server_id . '|' . $i->batch) as $group) {
            /** @var ServerTrashItem $first */
            $first = $group->first();
            try {
                if ($first->server) {
                    $this->files->setServer($first->server)->deleteFiles(self::DIRECTORY, [$first->batch]);
                }
            } catch (DaemonConnectionException $exception) {
                if ($exception->getStatusCode() !== 404) {
                    continue;
                }
            }
            ServerTrashItem::query()->whereIn('id', $group->pluck('id'))->delete();
            $count += $group->count();
        }

        return $count;
    }

    /**
     * Undo a copy from another server: the copied files are deleted and what the server had
     * before comes back. The server must be stopped.
     */
    private function undoClone(Server $server, ServerTrashItem $item): void
    {
        $this->requireStopped($server);

        try {
            $kept = collect($this->files->setServer($server)->getDirectory($item->trash_path))->pluck('name')->all();
        } catch (DaemonConnectionException $exception) {
            if ($exception->getStatusCode() === 404) {
                $item->delete();
                throw new DisplayException(trans('server_files.trash.errors.nothing_kept'));
            }
            throw $exception;
        }

        $current = collect($this->files->setServer($server)->getDirectory('/'))->pluck('name')->reject(fn ($n) => $n === self::DIRECTORY)->values()->all();
        if ($current !== []) {
            $this->files->setServer($server)->deleteFiles('/', $current);
        }
        if ($kept !== []) {
            $this->files->setServer($server)->renameFiles('/', array_map(fn ($n) => ['from' => $item->trash_path . '/' . $n, 'to' => $n], $kept));
        }
        $this->files->setServer($server)->deleteFiles(self::DIRECTORY, [$item->batch]);
        $item->delete();
    }

    public function requireStopped(Server $server): void
    {
        $state = (string) ($this->daemon->setServer($server)->getDetails()['state'] ?? '');
        if ($state !== 'offline') {
            throw new DisplayException(trans('server_files.trash.errors.stop_first'));
        }
    }

    private function removeBatchIfEmpty(Server $server, string $batch): void
    {
        if (!ServerTrashItem::query()->where('server_id', $server->id)->where('batch', $batch)->exists()) {
            try {
                $this->files->setServer($server)->deleteFiles(self::DIRECTORY, [$batch]);
            } catch (DaemonConnectionException) {
                // An empty folder left behind is harmless.
            }
        }
    }

    /**
     * "name" when it is free, else "name (restored)", "name (restored 2)", ... (before the extension of files).
     */
    private function freeName(string $name, array $taken, bool $isFile): string
    {
        if (!in_array($name, $taken, true)) {
            return $name;
        }

        $dot = $isFile ? strrpos($name, '.') : false;
        [$base, $ext] = $dot !== false && $dot > 0 ? [substr($name, 0, $dot), substr($name, $dot)] : [$name, ''];
        for ($i = 1; ; ++$i) {
            $candidate = $base . ' (' . trans('server_files.trash.restored_suffix') . ($i > 1 ? ' ' . $i : '') . ')' . $ext;
            if (!in_array($candidate, $taken, true)) {
                return $candidate;
            }
        }
    }
}
