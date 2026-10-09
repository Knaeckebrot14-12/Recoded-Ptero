<?php

namespace Pterodactyl\Services\Nodes;

use Carbon\CarbonImmutable;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Enum\JwtScope;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\NodeDrain;
use Pterodactyl\Models\Allocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Pterodactyl\Models\ServerTransfer;
use Pterodactyl\Models\NodeDrainServer;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Wings\DaemonTransferRepository;
use Pterodactyl\Exceptions\Http\Server\ServerStateConflictException;

/**
 * "Move all servers away" of the admin node page. The node goes into maintenance mode, then its
 * servers move one after another (a few at a time) to the best other node (NodePlacementService,
 * existing free allocations only) with the normal server transfers. Servers without a possible
 * target stay where they are. advance() moves things along; it runs every minute (p:nodes:drain)
 * and whenever the node page asks for the status.
 *
 * startReturn() undoes a finished or cancelled run: the servers it moved away (and only those, as
 * long as they are still on the node they were moved to) go back to this node, onto the
 * allocations they had before when those are still free.
 */
class NodeDrainService
{
    /** Transfers of one drain that run at the same time. */
    public const PARALLEL = 2;

    public function __construct(
        private ConnectionInterface $connection,
        private NodePlacementService $placement,
        private DaemonTransferRepository $daemonTransferRepository,
        private NodeJWTService $nodeJWTService,
    ) {
    }

    public static function latest(Node $node): ?NodeDrain
    {
        return NodeDrain::query()->where('node_id', $node->id)->orderByDesc('id')->first();
    }

    /**
     * The servers the node's latest "away" run moved off it that are still on the node they were
     * moved to (rows of that run). Empty while a run of the node is going on.
     *
     * @return \Illuminate\Support\Collection<int, NodeDrainServer>
     */
    public static function returnable(Node $node): Collection
    {
        $away = NodeDrain::query()->where('node_id', $node->id)->where('mode', NodeDrain::MODE_AWAY)->orderByDesc('id')->first();
        if (!$away || NodeDrain::query()->where('node_id', $node->id)->where('status', NodeDrain::STATUS_RUNNING)->exists()) {
            return collect();
        }

        return $away->servers()
            ->where('status', NodeDrainServer::STATUS_DONE)
            ->whereNotNull('server_transfer_id')
            ->whereNotNull('target_node_id')
            ->with('server')
            ->get()
            // Only servers this run moved itself (not ones somebody else was already transferring).
            ->filter(fn (NodeDrainServer $row) => empty($row->details['key']) && $row->server && (int) $row->server->node_id === (int) $row->target_node_id)
            ->values();
    }

    /**
     * Moves the servers of the latest "away" run back to the node and sets its maintenance mode
     * back to how it was before that run.
     *
     * @throws DisplayException when nothing can be moved back or a run is going on
     * @throws \Throwable
     */
    public function startReturn(Node $node, ?User $user): NodeDrain
    {
        $drain = $this->connection->transaction(function () use ($node, $user) {
            /** @var Node $locked */
            $locked = Node::query()->whereKey($node->id)->lockForUpdate()->firstOrFail();
            if (NodeDrain::query()->where('node_id', $node->id)->where('status', NodeDrain::STATUS_RUNNING)->exists()) {
                throw new DisplayException(trans('admin/placement.drain.already_running'));
            }
            $rows = self::returnable($node);
            if ($rows->isEmpty()) {
                throw new DisplayException(trans('admin/placement.drain.nothing_to_return'));
            }

            $drain = NodeDrain::query()->create([
                'node_id' => $node->id,
                'user_id' => $user?->id,
                'status' => NodeDrain::STATUS_RUNNING,
                'mode' => NodeDrain::MODE_BACK,
                'source_drain_id' => $rows->first()->node_drain_id,
                'maintenance_before' => (bool) $locked->maintenance_mode,
            ]);

            // The node takes servers again as it did before they were moved away.
            $away = NodeDrain::query()->findOrFail($rows->first()->node_drain_id);
            Node::query()->whereKey($node->id)->update(['maintenance_mode' => $away->maintenance_before]);

            foreach ($rows as $row) {
                $drain->servers()->create(['server_id' => $row->server_id, 'status' => NodeDrainServer::STATUS_QUEUED, 'target_node_id' => $node->id]);
            }

            return $drain;
        });

        $this->advance($drain);

        return $drain->refresh();
    }

    /**
     * @throws DisplayException when the servers of this node are already being moved away
     * @throws \Throwable
     */
    public function start(Node $node, ?User $user): NodeDrain
    {
        $drain = $this->connection->transaction(function () use ($node, $user) {
            /** @var Node $locked */
            $locked = Node::query()->whereKey($node->id)->lockForUpdate()->firstOrFail();
            if (NodeDrain::query()->where('node_id', $node->id)->where('status', NodeDrain::STATUS_RUNNING)->exists()) {
                throw new DisplayException(trans('admin/placement.drain.already_running'));
            }

            $drain = NodeDrain::query()->create([
                'node_id' => $node->id,
                'user_id' => $user?->id,
                'status' => NodeDrain::STATUS_RUNNING,
                'maintenance_before' => (bool) $locked->maintenance_mode,
            ]);

            // A plain query, so the node's validation rules and model events don't get involved.
            Node::query()->whereKey($node->id)->update(['maintenance_mode' => true]);

            foreach (Server::query()->where('node_id', $node->id)->orderBy('id')->pluck('id') as $serverId) {
                $drain->servers()->create(['server_id' => $serverId, 'status' => NodeDrainServer::STATUS_QUEUED]);
            }

            return $drain;
        });

        $this->advance($drain);

        return $drain->refresh();
    }

    /**
     * Stops starting new moves and sets maintenance mode back. Moves already running finish.
     */
    public function cancel(NodeDrain $drain): void
    {
        Cache::lock('node-drain:' . $drain->id, 120)->block(15, function () use ($drain) {
            $drain->refresh();
            if (!$drain->isRunning()) {
                return;
            }

            $this->connection->transaction(function () use ($drain) {
                $drain->servers()->where('status', NodeDrainServer::STATUS_QUEUED)->update(['status' => NodeDrainServer::STATUS_CANCELLED, 'details' => null]);
                $drain->update(['status' => NodeDrain::STATUS_CANCELLED, 'finished_at' => now()]);
                Node::query()->whereKey($drain->node_id)->update(['maintenance_mode' => $drain->maintenance_before]);
            });
        });
    }

    /**
     * Checks the running moves and starts the next ones. Does nothing while another call is busy
     * with the same drain.
     */
    public function advance(NodeDrain $drain): void
    {
        Cache::lock('node-drain:' . $drain->id, 120)->get(function () use ($drain) {
            $drain->refresh();
            $this->checkMoving($drain);
            if (!$drain->isRunning()) {
                return;
            }

            $this->startNext($drain);

            if (!$drain->servers()->whereIn('status', [NodeDrainServer::STATUS_QUEUED, NodeDrainServer::STATUS_MOVING])->exists()) {
                $drain->update(['status' => NodeDrain::STATUS_FINISHED, 'finished_at' => now()]);
            }
        });
    }

    /**
     * Moves are done when Wings reported back (Remote\Servers\ServerTransferController).
     */
    private function checkMoving(NodeDrain $drain): void
    {
        foreach ($drain->servers()->where('status', NodeDrainServer::STATUS_MOVING)->with('transfer')->get() as $row) {
            $successful = $row->transfer?->successful;
            if ($successful === true) {
                $row->update(['status' => NodeDrainServer::STATUS_DONE]);
            } elseif ($successful === false || is_null($row->transfer)) {
                $row->update(['status' => NodeDrainServer::STATUS_FAILED, 'details' => ['key' => 'transfer_failed']]);
            }
        }
    }

    private function startNext(NodeDrain $drain): void
    {
        if ($drain->isReturn()) {
            $this->startNextReturn($drain);

            return;
        }

        $slots = self::PARALLEL - $drain->servers()->where('status', NodeDrainServer::STATUS_MOVING)->count();

        foreach ($drain->servers()->where('status', NodeDrainServer::STATUS_QUEUED)->orderBy('id')->with('server')->get() as $row) {
            if ($slots <= 0) {
                return;
            }

            $server = $row->server;
            if ((int) $server->node_id !== $drain->node_id) {
                $row->update(['status' => NodeDrainServer::STATUS_DONE, 'details' => ['key' => 'gone']]);
                continue;
            }

            // Someone already started a transfer of this server; follow that one.
            if ($transfer = $server->transfer) {
                $row->update(['status' => NodeDrainServer::STATUS_MOVING, 'server_transfer_id' => $transfer->id, 'target_node_id' => $transfer->new_node, 'details' => ['key' => 'already_moving']]);
                --$slots;
                continue;
            }

            if (in_array($server->status, [Server::STATUS_INSTALL_FAILED, Server::STATUS_REINSTALL_FAILED], true)) {
                $row->update(['status' => NodeDrainServer::STATUS_FAILED, 'details' => ['key' => 'not_installed']]);
                continue;
            }
            if (!$server->isInstalled() || $server->status === Server::STATUS_RESTORING_BACKUP) {
                $row->update(['details' => ['key' => 'waiting']]);
                continue;
            }

            // The server keeps as many ports as it has now, all from existing free allocations of the target.
            $additional = $server->allocations()->whereKeyNot($server->allocation_id)->count();
            $placement = $this->placement->find((int) $server->memory, (int) $server->disk, (int) $server->cpu, null, [$drain->node_id], $additional);
            if (is_null($placement)) {
                $reasons = $this->placement->evaluate((int) $server->memory, (int) $server->disk, (int) $server->cpu, [], [$drain->node_id], 1 + $additional)
                    ->mapWithKeys(fn (array $node) => [$node['node']->name => $node['reason'] ?? 'ports'])->all();
                $row->update(['status' => NodeDrainServer::STATUS_NO_TARGET, 'details' => ['reasons' => $reasons]]);
                continue;
            }

            try {
                $transfer = $this->transfer($server, $placement);
            } catch (ServerStateConflictException) {
                $row->update(['details' => ['key' => 'waiting']]);
                continue;
            } catch (\Throwable $exception) {
                report($exception);
                $row->update(['status' => NodeDrainServer::STATUS_FAILED, 'target_node_id' => $placement['node']->id, 'details' => ['key' => 'daemon', 'error' => mb_substr($exception->getMessage(), 0, 300)]]);
                continue;
            }

            $row->update(['status' => NodeDrainServer::STATUS_MOVING, 'server_transfer_id' => $transfer->id, 'target_node_id' => $placement['node']->id, 'details' => null]);
            --$slots;
        }
    }

    /**
     * The next servers of a "back" run: each goes to this node, onto the allocations it had
     * before it was moved away when they are still free, else onto other free ones of the node.
     */
    private function startNextReturn(NodeDrain $drain): void
    {
        $slots = self::PARALLEL - $drain->servers()->where('status', NodeDrainServer::STATUS_MOVING)->count();
        $node = Node::query()->findOrFail($drain->node_id);

        foreach ($drain->servers()->where('status', NodeDrainServer::STATUS_QUEUED)->orderBy('id')->with('server')->get() as $row) {
            if ($slots <= 0) {
                return;
            }

            $server = $row->server;
            if ((int) $server->node_id === $drain->node_id) {
                $row->update(['status' => NodeDrainServer::STATUS_DONE, 'details' => ['key' => 'already_back']]);
                continue;
            }
            if ($transfer = $server->transfer) {
                $row->update(['status' => NodeDrainServer::STATUS_MOVING, 'server_transfer_id' => $transfer->id, 'details' => ['key' => 'already_moving']]);
                --$slots;
                continue;
            }
            if (in_array($server->status, [Server::STATUS_INSTALL_FAILED, Server::STATUS_REINSTALL_FAILED], true)) {
                $row->update(['status' => NodeDrainServer::STATUS_FAILED, 'details' => ['key' => 'not_installed']]);
                continue;
            }
            if (!$server->isInstalled() || $server->status === Server::STATUS_RESTORING_BACKUP) {
                $row->update(['details' => ['key' => 'waiting']]);
                continue;
            }

            $placement = $this->returnPlacement($drain, $node, $server);
            if (is_null($placement)) {
                $row->update(['status' => NodeDrainServer::STATUS_NO_TARGET, 'details' => ['key' => 'no_allocations']]);
                continue;
            }

            try {
                $transfer = $this->transfer($server, $placement);
            } catch (ServerStateConflictException) {
                $row->update(['details' => ['key' => 'waiting']]);
                continue;
            } catch (\Throwable $exception) {
                report($exception);
                $row->update(['status' => NodeDrainServer::STATUS_FAILED, 'details' => ['key' => 'daemon', 'error' => mb_substr($exception->getMessage(), 0, 300)]]);
                continue;
            }

            $row->update(['status' => NodeDrainServer::STATUS_MOVING, 'server_transfer_id' => $transfer->id, 'details' => null]);
            --$slots;
        }
    }

    /**
     * Free allocations of the node for a returning server, preferring the ones it had before it was
     * moved away. Never creates allocations; null when there are not enough free ones.
     *
     * @return array{node: Node, allocation: Allocation, additional: int[]}|null
     */
    private function returnPlacement(NodeDrain $drain, Node $node, Server $server): ?array
    {
        $before = NodeDrainServer::query()->where('node_drain_id', $drain->source_drain_id)->where('server_id', $server->id)->with('transfer')->first()?->transfer;
        $free = Allocation::query()->where('node_id', $node->id)->whereNull('server_id')->orderBy('ip')->orderBy('port')->get()->keyBy('id');
        $needed = $server->allocations()->whereKeyNot($server->allocation_id)->count();

        $primary = $before && $free->has($before->old_allocation) ? $free->get($before->old_allocation) : $free->first();
        if (!$primary) {
            return null;
        }
        $free->forget($primary->id);

        $additional = collect($before->old_additional_allocations ?? [])->filter(fn ($id) => $free->has($id))->take($needed)->values();
        $additional = $additional->merge($free->keys()->diff($additional)->take($needed - $additional->count()))->values();
        if ($additional->count() < $needed) {
            return null;
        }

        return ['node' => $node, 'allocation' => $primary, 'additional' => $additional->map(fn ($id) => (int) $id)->all()];
    }

    /**
     * Starts a normal server transfer, the same steps as Admin\Servers\ServerTransferController::transfer().
     *
     * @param array{node: Node, allocation: Allocation, additional: int[]} $placement
     *
     * @throws \Throwable
     */
    private function transfer(Server $server, array $placement): ServerTransfer
    {
        $server->validateTransferState();

        return $this->connection->transaction(function () use ($server, $placement) {
            $transfer = new ServerTransfer();

            $transfer->server_id = $server->id;
            $transfer->old_node = $server->node_id;
            $transfer->new_node = $placement['node']->id;
            $transfer->old_allocation = $server->allocation_id;
            $transfer->new_allocation = $placement['allocation']->id;
            $transfer->old_additional_allocations = $server->allocations->where('id', '!=', $server->allocation_id)->pluck('id')->values()->toArray();
            $transfer->new_additional_allocations = $placement['additional'];

            $transfer->save();

            // Reserve the allocations so nothing else gets them while the transfer runs.
            $ids = array_merge([$placement['allocation']->id], $placement['additional']);
            $reserved = Allocation::query()->whereIn('id', $ids)->whereNull('server_id')->update(['server_id' => $server->id]);
            if ($reserved !== count($ids)) {
                throw new \RuntimeException('The free allocations on the target node were just taken.');
            }

            // Generate a token for the destination node that the source node can use to authenticate with.
            $token = $this->nodeJWTService
                ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
                ->setSubject($server->uuid)
                ->setScopes(JwtScope::ServerTransfer)
                ->handle($transfer->newNode, $server->uuid);

            // Notify the source node of the pending outgoing transfer.
            $this->daemonTransferRepository->setServer($server)->notify($transfer->newNode, $token);

            return $transfer;
        });
    }
}
