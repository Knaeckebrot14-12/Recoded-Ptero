<?php

namespace Pterodactyl\Models;

use Carbon\Carbon;

/**
 * One failed login or 2FA attempt from an IP address.
 *
 * @property int $id
 * @property string $ip
 * @property string|null $username
 * @property string $type login|checkpoint
 * @property Carbon $created_at
 */
class LoginFailure extends Model
{
    public const RESOURCE_NAME = 'login_failure';

    public const UPDATED_AT = null;

    protected $table = 'login_failures';

    protected $fillable = ['ip', 'device', 'username', 'type'];

    protected $casts = ['created_at' => 'datetime'];

    public static array $validationRules = [
        'ip' => 'required|string|max:45',
        'username' => 'nullable|string|max:191',
        'type' => 'required|string|max:16',
    ];
}
