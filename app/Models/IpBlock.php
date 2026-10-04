<?php

namespace Pterodactyl\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An IP address that may not use the login endpoints for a while.
 *
 * @property int $id
 * @property string $ip
 * @property string $reason auto|manual
 * @property int $failures
 * @property string|null $usernames
 * @property Carbon $blocked_until
 * @property int|null $created_by
 * @property Carbon|null $unblocked_at
 * @property int|null $unblocked_by
 * @property Carbon|null $notified_at
 * @property Carbon $created_at
 */
class IpBlock extends Model
{
    public const RESOURCE_NAME = 'ip_block';

    public const UPDATED_AT = null;

    public const REASON_AUTO = 'auto';
    public const REASON_MANUAL = 'manual';

    protected $table = 'ip_blocks';

    protected $fillable = ['ip', 'reason', 'failures', 'usernames', 'blocked_until', 'created_by', 'unblocked_at', 'unblocked_by', 'notified_at'];

    protected $casts = [
        'failures' => 'integer',
        'blocked_until' => 'datetime',
        'unblocked_at' => 'datetime',
        'notified_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public static array $validationRules = [
        'ip' => 'required|string|max:45',
        'reason' => 'required|string|max:16',
        'failures' => 'integer|min:0',
        'usernames' => 'nullable|string',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('blocked_until', '>', now())->whereNull('unblocked_at');
    }

    /**
     * A block of a browser (device cookie) instead of an IP address; `ip` then holds "d:<hash>".
     */
    public function isDevice(): bool
    {
        return str_starts_with($this->ip, 'd:');
    }

    /**
     * @return string[]
     */
    public function usernameList(): array
    {
        return array_values(array_filter(explode("\n", (string) $this->usernames), fn ($name) => $name !== ''));
    }
}
