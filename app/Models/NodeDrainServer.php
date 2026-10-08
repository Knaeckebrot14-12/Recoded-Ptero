<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One server of a node drain and how far it got.
 *
 * @property int $id
 * @property int $node_drain_id
 * @property int $server_id
 * @property string $status queued|moving|done|failed|no_target|cancelled
 * @property int|null $target_node_id
 * @property int|null $server_transfer_id
 * @property array|null $details ['key' => ..., ...] or ['reasons' => [node name => reason]] for no_target
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property NodeDrain $drain
 * @property Server $server
 * @property Node|null $targetNode
 * @property ServerTransfer|null $transfer
 */
class NodeDrainServer extends Model
{
    public const RESOURCE_NAME = 'node_drain_server';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_MOVING = 'moving';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';
    public const STATUS_NO_TARGET = 'no_target';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'node_drain_servers';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'node_drain_id' => 'integer',
        'server_id' => 'integer',
        'target_node_id' => 'integer',
        'server_transfer_id' => 'integer',
        'details' => 'array',
    ];

    public static array $validationRules = [
        'node_drain_id' => 'required|integer',
        'server_id' => 'required|integer',
        'status' => 'required|in:queued,moving,done,failed,no_target,cancelled',
        'target_node_id' => 'nullable|integer',
        'server_transfer_id' => 'nullable|integer',
        'details' => 'nullable|array',
    ];

    public function drain(): BelongsTo
    {
        return $this->belongsTo(NodeDrain::class, 'node_drain_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function targetNode(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'target_node_id');
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(ServerTransfer::class, 'server_transfer_id');
    }
}
