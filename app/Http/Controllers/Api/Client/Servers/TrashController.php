<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Permission;
use Pterodactyl\Facades\Activity;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\ServerTrashItem;
use Pterodactyl\Services\Files\TrashService;
use Illuminate\Auth\Access\AuthorizationException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

/**
 * The file manager's trash: list, restore and delete for good (see TrashService).
 */
class TrashController extends ClientApiController
{
    public function __construct(private TrashService $trash)
    {
        parent::__construct();
    }

    public function index(Request $request, Server $server): JsonResponse
    {
        $this->requirePermission($request, $server, Permission::ACTION_FILE_READ);

        return new JsonResponse($this->payload($server));
    }

    public function restore(Request $request, Server $server): JsonResponse
    {
        $this->requirePermission($request, $server, Permission::ACTION_FILE_CREATE);
        $data = $request->validate(['ids' => 'required|array|max:200', 'ids.*' => 'integer']);

        $restored = $this->trash->restore($server, $data['ids']);

        Activity::event('server:file.restore')->property('files', $restored)->log();

        return new JsonResponse($this->payload($server));
    }

    /**
     * Deletes the given entries for good, or everything with "all".
     */
    public function destroy(Request $request, Server $server): JsonResponse
    {
        $this->requirePermission($request, $server, Permission::ACTION_FILE_DELETE);
        $data = $request->validate(['ids' => 'required_without:all|array|max:200', 'ids.*' => 'integer', 'all' => 'sometimes|boolean']);

        $all = (bool) ($data['all'] ?? false);
        $count = $this->trash->purge($server, $all ? null : $data['ids']);

        Activity::event('server:file.trash.purge')->property('count', $count)->property('all', $all)->log();

        return new JsonResponse($this->payload($server));
    }

    private function payload(Server $server): array
    {
        $hours = TrashService::hours();

        return [
            'hours' => $hours,
            'items' => $this->trash->items($server)->map(fn (ServerTrashItem $item) => [
                'id' => $item->id,
                'kind' => $item->kind,
                'name' => $item->kind === ServerTrashItem::KIND_CLONE ? null : basename($item->original_path),
                'path' => $item->original_path,
                'is_file' => $item->is_file,
                'size' => $item->size,
                'label' => $item->label,
                'deleted_at' => $item->created_at->toIso8601String(),
                'expires_at' => $item->created_at->addHours($hours)->toIso8601String(),
            ])->values(),
        ];
    }

    private function requirePermission(Request $request, Server $server, string $permission): void
    {
        if (!$request->user()->can($permission, $server)) {
            throw new AuthorizationException();
        }
    }
}
