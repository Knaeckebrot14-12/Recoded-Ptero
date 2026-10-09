<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One "Move all servers away" run of a node (see NodeDrainService).
 *
 * @property int $id
 * @property int $node_id
 * @property int|null $user_id
 * @property string $status running|finished|cancelled
 * @property string $mode away (move the servers off the node) or back (return the servers an "away" drain moved)
 * @property int|null $source_drain_id the "away" drain a "back" drain returns the servers of
 * @property bool $maintenance_before the node's maintenance mode before the drain started
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $finished_at
 * @property Node $node
 * @property User|null $user
 * @property \Illuminate\Database\Eloquent\Collection<int, NodeDrainServer> $servers
 */
class NodeDrain extends Model
{
    public const RESOURCE_NAME = 'node_drain';

    public const STATUS_RUNNING = 'running';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_CANCELLED = 'cancelled';

    public const MODE_AWAY = 'away';
    public const MODE_BACK = 'back';

    protected $table = 'node_drains';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'node_id' => 'integer',
        'user_id' => 'integer',
        'maintenance_before' => 'boolean',
        'source_drain_id' => 'integer',
        'finished_at' => 'datetime',
    ];

    public static array $validationRules = [
        'node_id' => 'required|integer',
        'user_id' => 'nullable|integer',
        'status' => 'required|in:running,finished,cancelled',
        'maintenance_before' => 'boolean',
        'mode' => 'in:away,back',
        'source_drain_id' => 'nullable|integer',
    ];

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isReturn(): bool
    {
        return $this->mode === self::MODE_BACK;
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function servers(): HasMany
    {
        return $this->hasMany(NodeDrainServer::class);
    }
}
