<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Support\Arr;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Coins\CoinService;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Services\Servers\ServerDeletionService;
use Pterodactyl\Services\Nodes\NodePlacementService;
use Pterodactyl\Services\Deployment\AllocationSelectionService;
use Pterodactyl\Http\Requests\Api\Client\SelfServiceServerRequest;

class SelfServiceServerController extends ClientApiController
{
    // How long a user must wait after creating a self-service server before
    // they're allowed to create another one.
    private const COOLDOWN_MINUTES = 10;

    public function __construct(
        private ServerCreationService $creationService,
        private ServerDeletionService $deletionService,
        private AllocationSelectionService $allocationSelectionService,
        private CoinService $coins,
        private NodePlacementService $placement,
    ) {
        parent::__construct();
    }

    /**
     * Servers that count against the user's free self-service resource pool.
     * Coin-funded servers are billed separately and excluded by default —
     * unless an admin has explicitly opted to have them count towards it too.
     */
    private function poolCountedServers(int $userId)
    {
        $query = Server::query()->where('owner_id', $userId);

        if (!config('coins.server.count_towards_pool')) {
            $query->whereNull('paid_with_coins_until');
        }

        return $query;
    }

    /**
     * Returns everything the create-server page needs to render: the user's
     * resource pool and how much of it is already used, the nodes they can
     * deploy to (with capacity), and the eggs they can choose from.
     */
    public function index(): JsonResponse
    {
        $user = request()->user();

        $owned = Server::query()->where('owner_id', $user->id);
        $poolCounted = $this->poolCountedServers($user->id);

        $nests = Nest::query()->with('eggs')->get();

        return new JsonResponse([
            'limits' => [
                'memory' => $user->server_memory_limit,
                'disk' => $user->server_disk_limit,
                'cpu' => $user->server_cpu_limit,
                'backups' => $user->server_backup_limit,
                'slots' => $user->server_slots,
            ],
            'used' => [
                'memory' => (int) (clone $poolCounted)->sum('memory'),
                'disk' => (int) (clone $poolCounted)->sum('disk'),
                'cpu' => (int) (clone $poolCounted)->sum('cpu'),
                'backups' => (int) (clone $poolCounted)->sum('backup_limit'),
                'slots' => (int) (clone $poolCounted)->count(),
            ],
            'suspended' => $user->isSuspended(),
            'cooldownSecondsRemaining' => $this->cooldownSecondsRemaining($user),
            'servers' => (clone $owned)->get()->map(fn (Server $server) => [
                'identifier' => $server->uuidShort,
                'name' => $server->name,
                'memory' => $server->memory,
                'disk' => $server->disk,
                'cpu' => $server->cpu,
                'backupLimit' => $server->backup_limit,
                'paidWithCoinsUntil' => $server->paid_with_coins_until?->toIso8601String(),
            ]),
            'nodes' => Node::query()->where('public', true)->get()->map(fn (Node $node) => [
                'id' => $node->id,
                'name' => $node->name,
                'servers' => $node->servers()->count(),
                'maximumServers' => $node->maximum_servers,
            ]),
            'nests' => $nests->map(fn (Nest $nest) => [
                'id' => $nest->id,
                'name' => $nest->name,
                'eggs' => $nest->eggs->map(fn (Egg $egg) => [
                    'id' => $egg->id,
                    'name' => $egg->name,
                ]),
            ]),
        ]);
    }

    /**
     * Creates a server owned by the requesting user. The resource values are
     * user-chosen, but are capped server-side by their resource pool — the
     * client cannot request more than they've been allotted in total across
     * all of their servers.
     *
     * @throws \Throwable
     */
    public function store(SelfServiceServerRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isSuspended()) {
            throw new DisplayException(trans('coins.errors.account_suspended'));
        }

        $cooldown = $this->cooldownSecondsRemaining($user);
        if ($cooldown > 0) {
            $minutes = (int) ceil($cooldown / 60);
            throw new DisplayException(
                trans('coins.errors.cooldown', ['minutes' => $minutes])
            );
        }

        // Start the cooldown right away and in one query: requests sent in parallel would otherwise all
        // pass the slot and resource checks below before the first server exists.
        $previousCreatedAt = $user->last_server_created_at;
        $started = User::query()
            ->whereKey($user->id)
            ->where(fn ($query) => $query->whereNull('last_server_created_at')
                ->orWhere('last_server_created_at', '<=', now()->subMinutes(self::COOLDOWN_MINUTES)))
            ->update(['last_server_created_at' => now()]);
        if (!$started) {
            throw new DisplayException(trans('coins.errors.cooldown', ['minutes' => self::COOLDOWN_MINUTES]));
        }

        try {
            return $this->createServer($request, $user);
        } catch (\Throwable $exception) {
            // Nothing was created, so this attempt doesn't count towards the cooldown.
            User::query()->whereKey($user->id)->update(['last_server_created_at' => $previousCreatedAt]);

            throw $exception;
        }
    }

    /**
     * @throws \Throwable
     */
    private function createServer(SelfServiceServerRequest $request, User $user): JsonResponse
    {

        $poolCounted = $this->poolCountedServers($user->id);
        $usedSlots = (clone $poolCounted)->count();
        if ($usedSlots >= $user->server_slots) {
            throw new DisplayException(trans('coins.errors.slots_used', ['slots' => $user->server_slots]));
        }

        $memory = (int) $request->input('memory');
        $disk = (int) $request->input('disk');
        $cpu = (int) $request->input('cpu');
        $backupLimit = (int) $request->input('backup_limit');

        $this->assertWithinPool($poolCounted, 'memory', $memory, $user->server_memory_limit, trans('coins.errors.unit_memory'));
        $this->assertWithinPool($poolCounted, 'disk', $disk, $user->server_disk_limit, trans('coins.errors.unit_disk'));
        $this->assertWithinPool($poolCounted, 'cpu', $cpu, $user->server_cpu_limit, trans('coins.errors.unit_cpu'));
        $this->assertWithinPool($poolCounted, 'backup_limit', $backupLimit, $user->server_backup_limit, trans('coins.errors.unit_backups'));

        $placement = null;
        if ($request->filled('node_id')) {
            /** @var Node $node */
            $node = Node::query()->where('public', true)->findOrFail($request->input('node_id'));
        } else {
            // "Automatic": the panel picks the best node and one of its existing free allocations.
            $placement = $this->placement->findOrFail($memory, $disk, $cpu);
            $node = $placement['node'];
        }

        $usedNodeMemory = (int) Server::query()->where('node_id', $node->id)->sum('memory');
        $usedNodeDisk = (int) Server::query()->where('node_id', $node->id)->sum('disk');
        if ($usedNodeMemory + $memory > $node->memory * (1 + $node->memory_overallocate / 100)) {
            throw new DisplayException(trans('coins.errors.node_memory'));
        }
        if ($usedNodeDisk + $disk > $node->disk * (1 + $node->disk_overallocate / 100)) {
            throw new DisplayException(trans('coins.errors.node_disk'));
        }

        if (!is_null($node->maximum_servers)) {
            $serverCount = $node->servers()->count();
            if ($serverCount >= $node->maximum_servers) {
                throw new DisplayException(
                    trans('coins.errors.node_full', ['count' => $serverCount, 'max' => $node->maximum_servers])
                );
            }
        }

        /** @var Egg $egg */
        $egg = Egg::query()->with('variables')->findOrFail($request->input('egg_id'));
        $environment = $egg->variables->pluck('default_value', 'env_variable')->toArray();

        $allocation = $placement['allocation'] ?? $this->allocationSelectionService
            ->setDedicated(false)
            ->setNodes([$node->id])
            ->setPorts([])
            ->handle();

        $server = $this->creationService->handle([
            'name' => $request->input('name'),
            'owner_id' => $user->id,
            'egg_id' => $egg->id,
            'nest_id' => $egg->nest_id,
            'image' => Arr::first($egg->docker_images),
            'startup' => $egg->startup,
            'environment' => $environment,
            'memory' => $memory,
            'swap' => 0,
            'disk' => $disk,
            'cpu' => $cpu,
            'threads' => null,
            'io' => 500,
            'database_limit' => 0,
            'allocation_limit' => 0,
            'backup_limit' => $backupLimit,
            'start_on_completion' => true,
            'skip_scripts' => false,
            'node_id' => $node->id,
            'allocation_id' => $allocation->id,
        ]);

        return new JsonResponse([
            'data' => [
                'identifier' => $server->uuidShort,
            ],
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * Deletes a server the requesting user owns. Servers assigned to them by
     * an admin, but not owned by them, cannot be removed this way.
     *
     * If the server was bought with coins and still has paid time left, a
     * percentage (admin-configurable) of the coin value of that unused time
     * is refunded before the server is removed.
     *
     * @throws \Throwable
     */
    public function destroy(Server $server): JsonResponse
    {
        $user = request()->user();

        if ($server->owner_id !== $user->id) {
            throw new DisplayException(trans('coins.errors.not_owner'));
        }

        $refund = $this->calculateCancellationRefund($server);
        $paidUntil = $server->paid_with_coins_until;
        if ($refund > 0) {
            // Take the paid time off the server in one query before refunding it, so deleting the
            // same server several times at once can't pay the refund more than once.
            $taken = Server::query()->whereKey($server->id)->where('paid_with_coins_until', $paidUntil)
                ->update(['paid_with_coins_until' => null]);
            if (!$taken) {
                $refund = 0;
            }
        }

        try {
            $this->deletionService->handle($server);
        } catch (\Throwable $exception) {
            if ($refund > 0) {
                Server::query()->whereKey($server->id)->update(['paid_with_coins_until' => $paidUntil]);
            }

            throw $exception;
        }

        if ($refund > 0) {
            $this->coins->credit(
                $user,
                $refund,
                'shop:server:cancellation_refund',
                "Cancelled server #{$server->id} — refund for unused time"
            );
        }

        return new JsonResponse(['refund' => $refund]);
    }

    /**
     * The coin refund owed for deleting a coin-funded server before its next
     * renewal is due: the configured percentage of the coin value of the
     * paid time that wouldn't be used. Zero for a server that was never
     * bought with coins, or one whose paid period has already lapsed.
     */
    private function calculateCancellationRefund(Server $server): int
    {
        if (!$server->paid_with_coins_until || !$server->paid_with_coins_until->isFuture()) {
            return 0;
        }

        $price = (int) ($server->coin_monthly_price ?? config('coins.server.monthly_price'));
        $refundPercent = (int) config('coins.server.cancellation_refund_percent');

        $periodEnd = $server->paid_with_coins_until;
        $periodStart = $periodEnd->clone()->subMonth();
        $totalSeconds = max(1, $periodStart->diffInSeconds($periodEnd));
        $remainingSeconds = now()->diffInSeconds($periodEnd, true);
        $fractionRemaining = min(1, $remainingSeconds / $totalSeconds);

        $remainingValue = $price * $fractionRemaining;

        return (int) round($remainingValue * ($refundPercent / 100));
    }

    private function cooldownSecondsRemaining($user): int
    {
        if (!$user->last_server_created_at) {
            return 0;
        }

        $secondsSince = now()->diffInSeconds($user->last_server_created_at, true);
        $cooldownSeconds = self::COOLDOWN_MINUTES * 60;

        return max(0, $cooldownSeconds - (int) $secondsSince);
    }

    private function assertWithinPool($owned, string $column, int $requested, int $limit, string $unitLabel): void
    {
        $used = (int) (clone $owned)->sum($column);
        if ($used + $requested > $limit) {
            $remaining = max(0, $limit - $used);
            throw new DisplayException(trans('coins.errors.pool_left', ['remaining' => $remaining, 'unit' => $unitLabel]));
        }
    }
}
