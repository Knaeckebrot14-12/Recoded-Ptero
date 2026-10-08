<?php

namespace Pterodactyl\Services\Nodes;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Illuminate\Support\Collection;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Services\Deployment\AllocationSelectionService;
use Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException;

/**
 * Picks the node a server should go on ("Automatic" when creating a server, and the target when a
 * node is drained). A node qualifies when it is public, not in maintenance, not offline, has room for
 * the server's memory and disk (with its over-allocation, the same sums as the self-service and
 * deployment checks), is below its server limit and has enough EXISTING free allocations.
 *
 * Allocations are never created here: a node without a free one simply doesn't qualify.
 */
class NodePlacementService
{
    public const REASON_PRIVATE = 'private';
    public const REASON_MAINTENANCE = 'maintenance';
    public const REASON_OFFLINE = 'offline';
    public const REASON_TOO_BIG = 'too_big';
    public const REASON_MEMORY = 'memory';
    public const REASON_DISK = 'disk';
    public const REASON_SERVERS = 'servers';
    public const REASON_ALLOCATIONS = 'allocations';

    public function __construct(
        private NodeResourceLimit $resourceLimit,
        private AllocationSelectionService $allocationSelectionService,
    ) {
    }

    /**
     * Every node (in the given locations, minus the excluded ones) with why it can't take the server,
     * qualifying nodes first and best first: most free memory (as a share of the node's limit) left
     * after placing the server, then most free disk.
     *
     * @param int[] $locations
     * @param int[] $exclude node IDs
     * @param int $allocations free allocations the server needs on the node
     *
     * @return Collection<int, array{node: Node, reason: string|null, free_memory: float, free_disk: float}>
     */
    public function evaluate(int $memory, int $disk, int $cpu = 0, array $locations = [], array $exclude = [], int $allocations = 1): Collection
    {
        $nodes = Node::query()
            ->when(!empty($locations), fn ($query) => $query->whereIn('location_id', $locations))
            ->when(!empty($exclude), fn ($query) => $query->whereNotIn('id', $exclude))
            ->orderBy('id')
            ->get();
        $ids = $nodes->pluck('id')->all();

        $used = Server::query()->whereIn('node_id', $ids)->groupBy('node_id')
            ->selectRaw('node_id, SUM(memory) as memory, SUM(disk) as disk, COUNT(*) as servers')
            ->get()->keyBy('node_id');

        // Servers on their way to a node don't count there until the transfer is done, but they need the room.
        $incoming = ServerTransfer::query()->whereNull('successful')->whereIn('new_node', $ids)
            ->join('servers', 'servers.id', '=', 'server_transfers.server_id')
            ->groupBy('new_node')
            ->selectRaw('new_node, SUM(servers.memory) as memory, SUM(servers.disk) as disk, COUNT(*) as servers')
            ->get()->keyBy('new_node');

        $free = Allocation::query()->whereIn('node_id', $ids)->whereNull('server_id')->groupBy('node_id')
            ->selectRaw('node_id, COUNT(*) as free')
            ->pluck('free', 'node_id');

        return $nodes->map(function (Node $node) use ($memory, $disk, $cpu, $allocations, $used, $incoming, $free) {
            $usedMemory = (int) ($used[$node->id]->memory ?? 0) + (int) ($incoming[$node->id]->memory ?? 0);
            $usedDisk = (int) ($used[$node->id]->disk ?? 0) + (int) ($incoming[$node->id]->disk ?? 0);
            $servers = (int) ($used[$node->id]->servers ?? 0) + (int) ($incoming[$node->id]->servers ?? 0);

            $memoryLimit = $node->memory * (1 + $node->memory_overallocate / 100);
            $diskLimit = $node->disk * (1 + $node->disk_overallocate / 100);

            return [
                'node' => $node,
                'reason' => $this->reason($node, $memory, $disk, $cpu, $allocations, $usedMemory, $usedDisk, $servers, (int) ($free[$node->id] ?? 0), $memoryLimit, $diskLimit),
                'free_memory' => $memoryLimit > 0 ? ($memoryLimit - $usedMemory - $memory) / $memoryLimit : 0.0,
                'free_disk' => $diskLimit - $usedDisk - $disk,
            ];
        })->sort(function (array $a, array $b) {
            return [is_null($b['reason']), round($b['free_memory'], 4), $b['free_disk'], $a['node']->id]
                <=> [is_null($a['reason']), round($a['free_memory'], 4), $a['free_disk'], $b['node']->id];
        })->values();
    }

    /**
     * The best node for a server and the existing free allocation(s) it gets there, or null when
     * no node qualifies. The deployment object narrows the search like the API's "deploy" block
     * (locations, port range, dedicated IP).
     *
     * @param int[] $exclude node IDs
     * @param int $additional free allocations needed besides the primary one
     *
     * @return array{node: Node, allocation: Allocation, additional: int[]}|null
     *
     * @throws DisplayException when the port range is invalid
     */
    public function find(int $memory, int $disk, int $cpu = 0, ?DeploymentObject $deployment = null, array $exclude = [], int $additional = 0): ?array
    {
        $deployment ??= new DeploymentObject();

        $rows = $this->evaluate($memory, $disk, $cpu, $deployment->getLocations(), $exclude, 1 + $additional);
        foreach ($rows->whereNull('reason') as $row) {
            try {
                // The same picker the API's automatic deployment uses, limited to this one node.
                $allocation = $this->allocationSelectionService->setDedicated($deployment->isDedicated())
                    ->setNodes([$row['node']->id])
                    ->setPorts($deployment->getPorts())
                    ->handle();
            } catch (NoViableAllocationException) {
                continue;
            }

            $extra = $additional > 0 ? Allocation::query()->where('node_id', $row['node']->id)->whereNull('server_id')
                ->whereKeyNot($allocation->id)->orderBy('port')->limit($additional)->pluck('id')->all() : [];
            if (count($extra) < $additional) {
                continue;
            }

            return ['node' => $row['node'], 'allocation' => $allocation, 'additional' => $extra];
        }

        return null;
    }

    /**
     * Like find(), but a clear error instead of null.
     *
     * @return array{node: Node, allocation: Allocation, additional: int[]}
     *
     * @throws DisplayException
     */
    public function findOrFail(int $memory, int $disk, int $cpu = 0, ?DeploymentObject $deployment = null): array
    {
        return $this->find($memory, $disk, $cpu, $deployment) ?? throw new DisplayException(trans('exceptions.deployment.no_placement'));
    }

    /**
     * "node: reason; node: reason" for the team, so they can see why nothing qualified.
     *
     * @param Collection<int, array{node: Node, reason: string|null}> $rows
     */
    public static function describe(Collection $rows): string
    {
        return $rows->map(fn (array $row) => $row['node']->name . ': ' . trans('admin/placement.reasons.' . ($row['reason'] ?? 'ports')))->implode('; ');
    }

    private function reason(Node $node, int $memory, int $disk, int $cpu, int $allocations, int $usedMemory, int $usedDisk, int $servers, int $free, float $memoryLimit, float $diskLimit): ?string
    {
        if (!$node->public) {
            return self::REASON_PRIVATE;
        }
        if ($node->maintenance_mode) {
            return self::REASON_MAINTENANCE;
        }
        if ((int) ($node->monitor_state['failures'] ?? 0) >= NodeMonitorService::OFFLINE_AFTER_FAILURES) {
            return self::REASON_OFFLINE;
        }
        if ($usedMemory + $memory > $memoryLimit) {
            return self::REASON_MEMORY;
        }
        if ($usedDisk + $disk > $diskLimit) {
            return self::REASON_DISK;
        }
        if (!is_null($node->maximum_servers) && $servers >= $node->maximum_servers) {
            return self::REASON_SERVERS;
        }
        if ($free < $allocations) {
            return self::REASON_ALLOCATIONS;
        }

        try {
            $this->resourceLimit->assertFits($node, $cpu, $memory, $disk);
        } catch (DisplayException) {
            return self::REASON_TOO_BIG;
        }

        return null;
    }
}
