<?php

namespace Pterodactyl\Services\Nodes;

use Pterodactyl\Models\Node;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Services\Notifications\PushService;
use Pterodactyl\Services\Notifications\DiscordWebhook;
use Pterodactyl\Repositories\Wings\DaemonConfigurationRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

/**
 * Asks each node's Wings how busy the machine is, keeps the history for the graphs and sends
 * alerts (Discord webhook + push to the team) when a node goes offline or runs out of disk or memory.
 */
class NodeMonitorService
{
    /** Failed checks in a row before a node counts as offline (one check per minute). */
    public const OFFLINE_AFTER_FAILURES = 2;

    /** An alert clears once the value is this many percent below its threshold again. */
    private const HYSTERESIS = 5;

    public function __construct(private DaemonConfigurationRepository $repository, private PushService $push)
    {
    }

    /**
     * Checks one node. $store adds a point to the graph history.
     */
    public function check(Node $node, bool $store = true): void
    {
        $state = $node->monitor_state ?? [];
        $usage = null;
        $version = $node->wings_version;
        $online = true;

        try {
            $usage = $this->repository->setNode($node)->getUtilization();
            $state['capable'] = true;
            $version = $usage['version'] ?? $version;
        } catch (DaemonConnectionException $exception) {
            if ($exception->getStatusCode() === 404) {
                // Wings is up but older than Recoded Ptero's build: no graphs, no self-update.
                $state['capable'] = false;
                try {
                    $version = $this->repository->setNode($node)->getSystemInformation()['version'] ?? $version;
                } catch (DaemonConnectionException) {
                }
            } else {
                $online = false;
            }
        }

        $this->handleOnline($node, $state, $online);
        if ($usage) {
            $this->handleThreshold($node, $state, 'disk', $usage['disk_used'] ?? 0, $usage['disk_total'] ?? 0, (int) config('mcpanel.monitoring.disk_percent'));
            $this->handleThreshold($node, $state, 'memory', $usage['memory_used'] ?? 0, $usage['memory_total'] ?? 0, (int) config('mcpanel.monitoring.memory_percent'));

            if ($store) {
                DB::table('node_stats')->insert([
                    'node_id' => $node->id,
                    'cpu' => (float) ($usage['cpu_percent'] ?? 0),
                    'memory_used' => (int) ($usage['memory_used'] ?? 0),
                    'memory_total' => (int) ($usage['memory_total'] ?? 0),
                    'disk_used' => (int) ($usage['disk_used'] ?? 0),
                    'disk_total' => (int) ($usage['disk_total'] ?? 0),
                    'load' => (float) ($usage['load'][0] ?? 0),
                    'created_at' => now(),
                ]);
            }
            $state['last'] = [
                'cpu' => (float) ($usage['cpu_percent'] ?? 0),
                'threads' => (int) ($usage['cpu_threads'] ?? 0),
                'memory_used' => (int) ($usage['memory_used'] ?? 0),
                'memory_total' => (int) ($usage['memory_total'] ?? 0),
                'disk_used' => (int) ($usage['disk_used'] ?? 0),
                'disk_total' => (int) ($usage['disk_total'] ?? 0),
                'load' => $usage['load'] ?? [0, 0, 0],
                'uptime' => (int) ($usage['uptime_seconds'] ?? 0),
            ];
        }

        // A plain query, so the node's validation rules and model events don't get involved.
        $update = ['monitor_state' => json_encode($state), 'wings_version' => $version ? mb_substr(ltrim($version, 'vV'), 0, 32) : null];
        if ($online) {
            $update['last_seen_at'] = now();
        }
        Node::query()->whereKey($node->id)->update($update);
    }

    private function handleOnline(Node $node, array &$state, bool $online): void
    {
        if ($online) {
            $wasDown = !empty($state['offline_alerted']);
            $state['failures'] = 0;
            $state['offline_alerted'] = false;
            if ($wasDown) {
                $this->alert($node, trans('admin/monitoring.alerts.online_title', ['node' => $node->name]), trans('admin/monitoring.alerts.online_body'), DiscordWebhook::COLOR_GREEN);
            }

            return;
        }

        $state['failures'] = ($state['failures'] ?? 0) + 1;
        if ($state['failures'] >= self::OFFLINE_AFTER_FAILURES && empty($state['offline_alerted'])) {
            $state['offline_alerted'] = true;
            if (filter_var(config('mcpanel.monitoring.notify_offline'), FILTER_VALIDATE_BOOLEAN)) {
                $this->alert($node, trans('admin/monitoring.alerts.offline_title', ['node' => $node->name]), trans('admin/monitoring.alerts.offline_body', ['fqdn' => $node->fqdn]), DiscordWebhook::COLOR_RED);
            }
        }
    }

    private function handleThreshold(Node $node, array &$state, string $what, int $used, int $total, int $threshold): void
    {
        if ($total <= 0 || $threshold <= 0 || $threshold > 100) {
            return;
        }

        $percent = (int) round($used / $total * 100);
        $key = $what . '_alerted';

        if ($percent >= $threshold && empty($state[$key])) {
            $state[$key] = true;
            $this->alert(
                $node,
                trans("admin/monitoring.alerts.{$what}_title", ['node' => $node->name, 'percent' => $percent]),
                trans("admin/monitoring.alerts.{$what}_body", ['used' => self::gib($used), 'total' => self::gib($total)]),
                DiscordWebhook::COLOR_ORANGE
            );
        } elseif ($percent < $threshold - self::HYSTERESIS && !empty($state[$key])) {
            $state[$key] = false;
            $this->alert($node, trans("admin/monitoring.alerts.{$what}_ok_title", ['node' => $node->name, 'percent' => $percent]), '', DiscordWebhook::COLOR_GREEN);
        }
    }

    private function alert(Node $node, string $title, string $body, int $color): void
    {
        DiscordWebhook::send(config('mcpanel.monitoring.discord_webhook'), $title, $body, $color, [
            trans('admin/monitoring.alerts.field_node') => $node->name,
            trans('admin/monitoring.alerts.field_fqdn') => $node->fqdn,
        ]);

        $this->push->sendToTeam($title, $body ?: $node->name, route('admin.nodes.view', $node->id));
    }

    public static function gib(int $bytes): string
    {
        return number_format($bytes / 1024 ** 3, 1) . ' GiB';
    }

    /**
     * Graph points for the admin node page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function history(Node $node, string $range): array
    {
        $since = $range === '7d' ? now()->subDays(7) : now()->subDay();
        $query = DB::table('node_stats')->where('node_id', $node->id)->where('created_at', '>=', $since);

        if ($range === '7d') {
            $rows = $query->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as t, AVG(cpu) as cpu, AVG(memory_used) as memory_used, MAX(memory_total) as memory_total, MAX(disk_used) as disk_used, MAX(disk_total) as disk_total")
                ->groupBy('t')->orderBy('t')->get();
        } else {
            // 5-minute averages are plenty for a day.
            $rows = $query->selectRaw('FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(created_at) / 300) * 300) as t, AVG(cpu) as cpu, AVG(memory_used) as memory_used, MAX(memory_total) as memory_total, MAX(disk_used) as disk_used, MAX(disk_total) as disk_total')
                ->groupBy('t')->orderBy('t')->get();
        }

        return $rows->map(fn ($row) => [
            't' => \Carbon\Carbon::parse($row->t)->toIso8601String(),
            'cpu' => round((float) $row->cpu, 1),
            'memory' => $row->memory_total > 0 ? round($row->memory_used / $row->memory_total * 100, 1) : 0,
            'disk' => $row->disk_total > 0 ? round($row->disk_used / $row->disk_total * 100, 1) : 0,
        ])->values()->all();
    }
}
