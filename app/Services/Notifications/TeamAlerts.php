<?php

namespace Pterodactyl\Services\Notifications;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Ticket;
use Pterodactyl\Models\IpBlock;
use Pterodactyl\Services\Security\IpLockoutService;

/**
 * Messages to the team's Discord channel (the webhook from Admin -> Settings -> Monitoring) about
 * new support tickets and new registrations. They go out after the response was sent, so the
 * person who just opened a ticket or registered never waits for Discord, and a failing webhook
 * can never make either of them fail.
 */
class TeamAlerts
{
    public static function newTicket(Ticket $ticket): void
    {
        if (!self::enabled('notify_tickets')) {
            return;
        }

        $url = route('admin.tickets.view', $ticket->id);
        $subject = $ticket->subject;
        $username = $ticket->user?->username ?? '-';
        $category = $ticket->category;
        $priority = $ticket->priority;
        $id = $ticket->id;

        self::later(fn () => DiscordWebhook::send(
            config('mcpanel.monitoring.discord_webhook'),
            trans('admin/monitoring.team.ticket_title', ['id' => $id]),
            $subject . "\n" . $url,
            DiscordWebhook::COLOR_BLUE,
            [
                trans('admin/monitoring.team.field_user') => $username,
                trans('admin/monitoring.team.field_category') => (string) $category,
                trans('admin/monitoring.team.field_priority') => (string) $priority,
            ]
        ));
    }

    public static function newRegistration(User $user): void
    {
        if (!self::enabled('notify_registrations')) {
            return;
        }

        $username = $user->username;
        $url = route('admin.users.view', $user->id);

        self::later(fn () => DiscordWebhook::send(
            config('mcpanel.monitoring.discord_webhook'),
            trans('admin/monitoring.team.registration_title'),
            $username . "\n" . $url,
            DiscordWebhook::COLOR_GREEN
        ));
    }

    /**
     * An IP address was blocked for too many failed logins. The caller (IpLockoutService) already
     * made sure this is the first block of that IP in 24 hours and that no more than 5 messages
     * per hour go out, so a botnet can't flood the channel.
     */
    public static function ipBlocked(IpBlock $block): void
    {
        if (!DiscordWebhook::isValidUrl(config('mcpanel.monitoring.discord_webhook'))) {
            return;
        }

        // A browser is shown as "Browser d:abc123…", never with its full id.
        $ip = $block->isDevice() ? trans('admin/ipblock.device') . ' ' . substr($block->ip, 0, 8) . '…' : $block->ip;
        $failures = $block->failures;
        $minutes = max(1, (int) round($block->created_at->diffInMinutes($block->blocked_until, true)));
        $until = $block->blocked_until->copy()->setTimezone(config('app.timezone'))->format('d.m.Y H:i T');
        $usernames = implode(', ', array_slice($block->usernameList(), 0, 10));

        $fields = [trans('admin/ipblock.discord.field_until') => $until];
        if ($usernames !== '') {
            $fields[trans('admin/ipblock.discord.field_usernames')] = $usernames;
        }

        self::later(fn () => DiscordWebhook::send(
            config('mcpanel.monitoring.discord_webhook'),
            trans('admin/ipblock.discord.title'),
            trans('admin/ipblock.discord.body', ['ip' => $ip, 'failures' => $failures, 'duration' => IpLockoutService::durationLabel($minutes)])
                . "\n" . route('admin.security.ipblocks'),
            DiscordWebhook::COLOR_ORANGE,
            $fields
        ));
    }

    private static function enabled(string $setting): bool
    {
        return DiscordWebhook::isValidUrl(config('mcpanel.monitoring.discord_webhook'))
            && filter_var(config('mcpanel.monitoring.' . $setting), FILTER_VALIDATE_BOOLEAN);
    }

    private static function later(\Closure $send): void
    {
        app()->terminating(function () use ($send) {
            try {
                $send();
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }
}
