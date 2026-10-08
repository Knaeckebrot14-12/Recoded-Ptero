<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Facades\Activity;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Files\TrashService;
use Pterodactyl\Services\Servers\ServerCloneService;
use Illuminate\Auth\Access\AuthorizationException;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

/**
 * Settings -> "Copy this server": copy all files into another server of the same type.
 */
class CloneController extends ClientApiController
{
    public function __construct(private ServerCloneService $clone)
    {
        parent::__construct();
    }

    /**
     * "visible" is false unless the person has at least one more server of this type.
     */
    public function index(Request $request, Server $server): JsonResponse
    {
        $candidates = $this->clone->candidates($request->user(), $server);

        return new JsonResponse([
            'visible' => $candidates->isNotEmpty(),
            'supported' => ServerCloneService::supported($server),
            'min_wings_version' => ServerCloneService::MIN_WINGS_VERSION,
            'trash_hours' => TrashService::hours(),
            'targets' => $candidates->map(fn (array $c) => [
                'uuid' => $c['server']->uuid,
                'identifier' => $c['server']->uuidShort,
                'name' => $c['server']->name,
                'eligible' => $c['eligible'],
                'reason' => $c['reason'],
            ])->values(),
        ]);
    }

    public function store(Request $request, Server $server): JsonResponse
    {
        $data = $request->validate(['target' => 'required|string|max:64']);
        $target = Server::query()->where('uuid', $data['target'])->first();
        if (!$target) {
            throw new DisplayException(trans('server_clone.errors.not_allowed'));
        }

        $this->clone->start($request->user(), $server, $target);

        // Logged on both servers (the one in the URL is the subject anyway).
        Activity::event('server:clone')->subject($target)->property('source', $server->name)->property('target', $target->name)->log();

        return new JsonResponse($this->clone->status($target) ?? ['state' => 'running'], 202);
    }

    /**
     * Progress of the copy into ?target= (which the person must be able to manage).
     */
    public function status(Request $request, Server $server): JsonResponse
    {
        $target = Server::query()->where('uuid', (string) $request->query('target'))->first();
        if (!$target || !$this->clone->candidates($request->user(), $server)->contains(fn ($c) => $c['server']->id === $target->id)) {
            throw new AuthorizationException();
        }

        return new JsonResponse($this->clone->status($target) ?? ['state' => 'none']);
    }
}
