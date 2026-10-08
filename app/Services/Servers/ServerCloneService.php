<?php

namespace Pterodactyl\Services\Servers;

use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Permission;
use Illuminate\Support\Collection;
use Pterodactyl\Models\ServerTrashItem;
use Pterodactyl\Services\Files\TrashService;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Wings\DaemonCloneRepository;

/**
 * "Clone": copies all files of a server into another server of the same type (egg) on the same
 * node that the same person may manage. No server, port or allocation is created. The target's
 * previous files go into its trash (restorable for a while) when there is room, otherwise they
 * are deleted; Wings does the copy (needs Recoded Ptero's Wings MIN_WINGS_VERSION or newer).
 */
class ServerCloneService
{
    public const MIN_WINGS_VERSION = '1.2.0';

    public function __construct(private DaemonCloneRepository $daemon)
    {
    }

    public static function supported(Server $server): bool
    {
        $version = (string) optional($server->node)->wings_version;

        return $version !== '' && version_compare(ltrim($version, 'vV'), self::MIN_WINGS_VERSION, '>=');
    }

    /**
     * The person's other servers of the same type, each with whether it can be the target and why not.
     *
     * @return \Illuminate\Support\Collection<int, array{server: Server, eligible: bool, reason: ?string}>
     */
    public function candidates(User $user, Server $source): Collection
    {
        return $user->accessibleServers()
            ->with('node')
            ->where('servers.egg_id', $source->egg_id)
            ->where('servers.id', '!=', $source->id)
            ->orderBy('servers.name')
            ->get()
            ->map(function (Server $target) use ($user, $source) {
                $reason = match (true) {
                    $target->node_id !== $source->node_id => 'other_node',
                    !$user->can(Permission::ACTION_FILE_DELETE, $target) || !$user->can(Permission::ACTION_FILE_CREATE, $target) => 'no_permission',
                    $target->isSuspended() || $target->status !== null => 'unavailable',
                    default => null,
                };

                return ['server' => $target, 'eligible' => $reason === null, 'reason' => $reason];
            });
    }

    /**
     * Starts the copy. Returns the batch (the target's trash folder for its previous files).
     */
    public function start(User $user, Server $source, Server $target): string
    {
        if (!$user->can(Permission::ACTION_FILE_READ_CONTENT, $source)) {
            throw new DisplayException(trans('server_clone.errors.no_permission'));
        }
        $candidate = $this->candidates($user, $source)->first(fn ($c) => $c['server']->id === $target->id);
        if (!$candidate) {
            throw new DisplayException(trans('server_clone.errors.not_allowed'));
        }
        if (!$candidate['eligible']) {
            throw new DisplayException(trans('server_clone.errors.' . $candidate['reason']));
        }
        if ($source->isSuspended() || $source->status !== null) {
            throw new DisplayException(trans('server_clone.errors.unavailable'));
        }
        if (!self::supported($source)) {
            throw new DisplayException(trans('server_clone.errors.wings_too_old', ['version' => self::MIN_WINGS_VERSION]));
        }

        $batch = now()->timestamp . '-' . Str::lower(Str::random(6));

        // Recorded first, so the previous files can be found in the trash even if nobody watches the copy.
        $item = ServerTrashItem::query()->create([
            'server_id' => $target->id,
            'user_id' => $user->id,
            'kind' => ServerTrashItem::KIND_CLONE,
            'batch' => $batch,
            'original_path' => '/',
            'trash_path' => TrashService::DIRECTORY . '/' . $batch,
            'is_file' => false,
            'size' => 0,
            'label' => Str::limit($source->name, 180),
        ]);

        try {
            $this->daemon->setServer($target)->start($source, $batch);
        } catch (\Throwable $exception) {
            $item->delete();
            throw $exception;
        }

        return $batch;
    }

    /**
     * The progress of the last copy into $target as the client sees it. When Wings had to delete
     * the previous files (not enough room to keep them), the trash entry for them is removed.
     */
    public function status(Server $target): ?array
    {
        $status = $this->daemon->setServer($target)->status();
        if ($status === null) {
            return null;
        }

        // Nothing was kept (no room, or the copy failed before touching anything): no trash entry.
        $batch = (string) ($status['batch'] ?? '');
        if ($batch !== '' && ($status['state'] ?? '') !== 'running' && !($status['trashed'] ?? false)) {
            ServerTrashItem::query()->where('server_id', $target->id)->where('kind', ServerTrashItem::KIND_CLONE)->where('batch', $batch)->delete();
        }

        $source = Server::query()->where('uuid', (string) ($status['source'] ?? ''))->first();

        return [
            'state' => (string) ($status['state'] ?? 'failed'),
            'error' => $status['error'] ?? null,
            'source' => $source?->name,
            'kept_in_trash' => (bool) ($status['trashed'] ?? false),
            'files' => (int) ($status['files'] ?? 0),
            'bytes' => (int) ($status['bytes'] ?? 0),
            'started_at' => $status['started_at'] ?? null,
            'finished_at' => $status['finished_at'] ?? null,
        ];
    }
}
