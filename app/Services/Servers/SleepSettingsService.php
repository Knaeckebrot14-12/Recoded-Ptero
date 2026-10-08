<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;

/**
 * Sleep mode ("sleep when empty"): Wings stops a server nobody has been on for a while and
 * answers connection attempts itself, starting the server when a player joins. A server follows
 * the panel-wide default (Admin -> Settings -> Advanced) until its owner sets it explicitly.
 */
class SleepSettingsService
{
    /** Idle times (minutes) that can be picked. */
    public const MINUTES = [10, 15, 30, 60, 120, 240];

    /** The first Wings release that understands the settings (Recoded Ptero's own build). */
    public const MIN_WINGS_VERSION = '1.1.0';

    public static function defaultEnabled(): bool
    {
        return filter_var(config('mcpanel.sleep.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public static function defaultMinutes(): int
    {
        $minutes = (int) config('mcpanel.sleep.minutes', 30);

        return in_array($minutes, self::MINUTES, true) ? $minutes : 30;
    }

    public function enabled(Server $server): bool
    {
        return $server->sleep_enabled ?? self::defaultEnabled();
    }

    public function minutes(Server $server): int
    {
        $minutes = (int) $server->sleep_minutes;

        return in_array($minutes, self::MINUTES, true) ? $minutes : self::defaultMinutes();
    }

    /**
     * Whether the server's Wings is new enough to do anything with the setting.
     */
    public function supported(Server $server): bool
    {
        $version = (string) optional($server->node)->wings_version;

        return $version !== '' && version_compare(ltrim($version, 'v'), self::MIN_WINGS_VERSION, '>=');
    }

    /**
     * The "sleep" block of the server configuration that Wings fetches. The two texts are what
     * players see in their server list and when they join a sleeping server, in the panel's language.
     */
    public function forWings(Server $server): array
    {
        $locale = (string) config('app.locale', 'en');

        return [
            'enabled' => $this->enabled($server) && !$server->isSuspended(),
            'minutes' => $this->minutes($server),
            'motd' => trans('server_sleep.minecraft.motd', [], $locale),
            'starting' => trans('server_sleep.minecraft.starting', [], $locale),
        ];
    }
}
