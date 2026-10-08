<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Permission;
use Pterodactyl\Facades\Activity;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Pterodactyl\Services\Servers\SleepSettingsService;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;

class SleepController extends ClientApiController
{
    public function __construct(private SleepSettingsService $sleep, private DaemonServerRepository $daemon)
    {
        parent::__construct();
    }

    public function show(Request $request, Server $server): JsonResponse
    {
        $this->requirePermission($request, $server, Permission::ACTION_SETTINGS_RENAME);

        return new JsonResponse($this->payload($server));
    }

    /**
     * "enabled" and "minutes" may be null: the server then follows the panel-wide default again.
     */
    public function update(Request $request, Server $server): JsonResponse
    {
        $this->requirePermission($request, $server, Permission::ACTION_SETTINGS_RENAME);
        $data = $request->validate([
            'enabled' => ['present', 'nullable', 'boolean'],
            'minutes' => ['present', 'nullable', 'integer', Rule::in(SleepSettingsService::MINUTES)],
        ]);

        $server->forceFill(['sleep_enabled' => $data['enabled'], 'sleep_minutes' => $data['minutes']])->save();

        Activity::event('server:sleep.update')
            ->property('enabled', $this->sleep->enabled($server))
            ->property('minutes', $this->sleep->minutes($server))
            ->log();

        // Wings picks the new settings up right away; a node that is offline gets them when the server starts.
        try {
            $this->daemon->setServer($server)->sync();
        } catch (\Throwable $exception) {
            report($exception);
        }

        return new JsonResponse($this->payload($server));
    }

    private function payload(Server $server): array
    {
        return [
            'enabled' => $this->sleep->enabled($server),
            'minutes' => $this->sleep->minutes($server),
            'custom' => $server->sleep_enabled !== null || $server->sleep_minutes !== null,
            'default_enabled' => SleepSettingsService::defaultEnabled(),
            'default_minutes' => SleepSettingsService::defaultMinutes(),
            'options' => SleepSettingsService::MINUTES,
            'supported' => $this->sleep->supported($server),
        ];
    }

    private function requirePermission(Request $request, Server $server, string $permission): void
    {
        if (!$request->user()->can($permission, $server)) {
            throw new AuthorizationException();
        }
    }
}
