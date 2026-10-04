<?php

namespace Pterodactyl\Models;

use Pterodactyl\Rules\Username;
use Pterodactyl\Facades\Activity;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\In;
use Illuminate\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Contracts\Models\Identifiable;
use Pterodactyl\Models\Traits\HasAccessTokens;
use Illuminate\Auth\Passwords\CanResetPassword;
use Pterodactyl\Traits\Helpers\AvailableLanguages;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Pterodactyl\Models\Traits\HasRealtimeIdentifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Pterodactyl\Notifications\SendPasswordReset as ResetPasswordNotification;

/**
 * Pterodactyl\Models\User.
 *
 * @property int $id
 * @property string|null $external_id
 * @property string $uuid
 * @property string $username
 * @property string $email
 * @property string|null $name_first
 * @property string|null $name_last
 * @property string $password
 * @property string|null $remember_token
 * @property string $language
 * @property bool $root_admin
 * @property bool $use_totp
 * @property string|null $totp_secret
 * @property \Illuminate\Support\Carbon|null $totp_authenticated_at
 * @property bool $gravatar
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\ApiKey[] $apiKeys
 * @property int|null $api_keys_count
 * @property string $name
 * @property \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property int|null $notifications_count
 * @property \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\RecoveryToken[] $recoveryTokens
 * @property int|null $recovery_tokens_count
 * @property \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\Server[] $servers
 * @property int|null $servers_count
 * @property \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\UserSSHKey[] $sshKeys
 * @property int|null $ssh_keys_count
 * @property \Illuminate\Database\Eloquent\Collection|\Pterodactyl\Models\ApiKey[] $tokens
 * @property int|null $tokens_count
 *
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereExternalId($value)
 * @method static Builder|User whereGravatar($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereLanguage($value)
 * @method static Builder|User whereNameFirst($value)
 * @method static Builder|User whereNameLast($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User whereRememberToken($value)
 * @method static Builder|User whereRootAdmin($value)
 * @method static Builder|User whereTotpAuthenticatedAt($value)
 * @method static Builder|User whereTotpSecret($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @method static Builder|User whereUseTotp($value)
 * @method static Builder|User whereUsername($value)
 * @method static Builder|User whereUuid($value)
 *
 * @mixin \Eloquent
 */
#[Attributes\Identifiable('user')]
class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    Identifiable
{
    use Authenticatable;
    use Authorizable;
    use AvailableLanguages;
    use CanResetPassword;
    /** @use \Pterodactyl\Models\Traits\HasAccessTokens<\Pterodactyl\Models\ApiKey> */
    use HasAccessTokens;
    use Notifiable;
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasRealtimeIdentifier;

    public const USER_LEVEL_USER = 0;
    public const USER_LEVEL_ADMIN = 1;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'user';

    /**
     * Level of servers to display when using access() on a user.
     */
    protected string $accessLevel = 'all';

    /**
     * The table associated with the model.
     */
    protected $table = 'users';

    /**
     * A list of mass-assignable variables.
     */
    protected $fillable = [
        'external_id',
        'username',
        'email',
        'name_first',
        'name_last',
        'password',
        'language',
        'use_totp',
        'totp_secret',
        'totp_authenticated_at',
        'gravatar',
        'root_admin',
        'server_memory_limit',
        'server_disk_limit',
        'server_cpu_limit',
        'server_backup_limit',
        'server_slots',
        'suspended_at',
        'last_server_created_at',
        'must_change_password',
        'coins',
        'last_afk_tick_at',
        'email_verified_at',
        'registration_ip',
        'discord_id',
        'discord_username',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'root_admin' => 'boolean',
        'use_totp' => 'boolean',
        'gravatar' => 'boolean',
        'totp_authenticated_at' => 'datetime',
        'server_memory_limit' => 'integer',
        'server_disk_limit' => 'integer',
        'server_cpu_limit' => 'integer',
        'server_backup_limit' => 'integer',
        'server_slots' => 'integer',
        'suspended_at' => 'datetime',
        'last_server_created_at' => 'datetime',
        'must_change_password' => 'boolean',
        'coins' => 'integer',
        'last_afk_tick_at' => 'datetime',
        'last_daily_claim_at' => 'datetime',
        'daily_streak' => 'integer',
        'referral_rewarded_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     */
    protected $hidden = ['password', 'remember_token', 'totp_secret', 'totp_authenticated_at', 'registration_ip'];

    /**
     * Default values for specific fields in the database.
     */
    protected $attributes = [
        'external_id' => null,
        'root_admin' => false,
        'role' => 'user',
        'use_totp' => false,
        'totp_secret' => null,
        'server_memory_limit' => 2048,
        'server_disk_limit' => 5120,
        'server_cpu_limit' => 100,
        'server_backup_limit' => 1,
        'server_slots' => 2,
        'must_change_password' => false,
        'coins' => 0,
    ];

    public const ROLE_USER = 'user';
    public const ROLE_SUPPORTER = 'supporter';
    public const ROLE_MODERATOR = 'moderator';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_OWNER = 'owner';

    /**
     * Roles from lowest to highest rank.
     */
    public const ROLES = [
        self::ROLE_USER => 0,
        self::ROLE_SUPPORTER => 1,
        self::ROLE_MODERATOR => 2,
        self::ROLE_ADMIN => 3,
        self::ROLE_OWNER => 4,
    ];

    /**
     * These listeners are registered before the base model's boot() on purpose: its validating
     * "saving" listener returns true, which stops Laravel from calling any listener added after it.
     */
    protected static function boot()
    {
        static::creating(function (User $user) {
            if (empty($user->referral_code)) {
                do {
                    $code = strtoupper(\Illuminate\Support\Str::random(8));
                } while (static::query()->where('referral_code', $code)->exists());

                $user->referral_code = $code;
            }

            // Accounts made by the team, the CLI or the installer are trusted; only self-registration
            // clears this afterwards when e-mail confirmation is required.
            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
            }
        });

        // Keep the legacy root_admin flag and the role column consistent with each other.
        static::saving(function (User $user) {
            $role = array_key_exists($user->role ?? '', self::ROLES) ? $user->role : self::ROLE_USER;

            if ($user->isDirty('role')) {
                $user->root_admin = in_array($role, [self::ROLE_ADMIN, self::ROLE_OWNER], true);
            } elseif ($user->isDirty('root_admin')) {
                if ($user->root_admin && self::ROLES[$role] < self::ROLES[self::ROLE_ADMIN]) {
                    $user->role = self::ROLE_ADMIN;
                } elseif (!$user->root_admin && self::ROLES[$role] >= self::ROLES[self::ROLE_ADMIN]) {
                    $user->role = self::ROLE_USER;
                }
            }
        });

        parent::boot();
    }

    /**
     * The user's role, treating any legacy root admin as at least an admin.
     */
    public function effectiveRole(): string
    {
        $role = array_key_exists($this->role ?? '', self::ROLES) ? $this->role : self::ROLE_USER;

        if ($this->root_admin && self::ROLES[$role] < self::ROLES[self::ROLE_ADMIN]) {
            return self::ROLE_ADMIN;
        }

        return $role;
    }

    public function roleRank(): int
    {
        return self::ROLES[$this->effectiveRole()];
    }

    /**
     * True for any team member (supporter, moderator, admin, owner).
     */
    public function isStaff(): bool
    {
        return $this->roleRank() > 0;
    }

    public function isOwner(): bool
    {
        return $this->effectiveRole() === self::ROLE_OWNER;
    }

    /**
     * Whether this team member may use a part of the admin area. The owner may do everything;
     * the other roles get what the owner set under Admin -> Settings -> Roles.
     */
    public function hasStaffPermission(string $permission): bool
    {
        $role = $this->effectiveRole();
        if ($role === self::ROLE_USER) {
            return false;
        }

        return \Pterodactyl\Services\Users\RolePermissions::grants($role, $permission);
    }

    /**
     * Whether this user may see the e-mail address of $other (always their own).
     */
    public function canSeeEmailOf(?User $other): bool
    {
        return $other === null || $this->is($other) || $this->hasStaffPermission('users.email');
    }

    /**
     * The e-mail address of $other as this user may see it.
     */
    public function visibleEmail(?User $other): string
    {
        if (!$other) {
            return '';
        }

        return $this->canSeeEmailOf($other) ? $other->email : trans('admin/users.email_hidden');
    }

    /**
     * Whether this user outranks the given user and may therefore moderate them.
     */
    public function outranks(User $other): bool
    {
        // The main owner may also manage (and demote) the other owners they handed the role to.
        if ($this->isMainOwner() && $other->isOwner() && !$this->is($other)) {
            return true;
        }

        return $this->roleRank() > $other->roleRank();
    }

    /**
     * The main owner is the first owner of the panel (the owner account with the lowest ID, which
     * is the account the installer made). Owners given the role later can't take it from them.
     */
    public function isMainOwner(): bool
    {
        if (!$this->isOwner()) {
            return false;
        }

        $first = static::query()->where('role', self::ROLE_OWNER)->orderBy('id')->value('id');

        return $first === null || (int) $first === (int) $this->id;
    }

    /**
     * Roles this user is allowed to hand out to others.
     */
    public function assignableRoles(): array
    {
        if ($this->isOwner()) {
            return array_keys(self::ROLES);
        }
        if (!$this->hasStaffPermission('users.roles')) {
            return [];
        }

        // Only roles below one's own, so nobody can promote others to their level or above.
        return array_keys(array_filter(self::ROLES, fn (int $rank) => $rank < $this->roleRank()));
    }

    /**
     * New accounts start with the panel-wide default language configured by the admin.
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes['language'] = config('app.default_locale', config('app.locale', 'en'));

        parent::__construct($attributes);
    }

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static array $validationRules = [
        'uuid' => 'required|string|size:36|unique:users,uuid',
        'email' => 'required|email:strict|between:1,191|unique:users,email',
        'external_id' => 'sometimes|nullable|string|max:191|unique:users,external_id',
        'username' => 'required|between:1,191|unique:users,username',
        'name_first' => 'required|string|between:1,191',
        'name_last' => 'required|string|between:1,191',
        'password' => 'sometimes|nullable|string',
        'root_admin' => 'boolean',
        'language' => 'string',
        'role' => 'string|in:user,supporter,moderator,admin,owner',
        'use_totp' => 'boolean',
        'totp_secret' => 'nullable|string',
        'server_memory_limit' => 'integer|min:0',
        'server_disk_limit' => 'integer|min:0',
        'server_cpu_limit' => 'integer|min:0',
        'server_backup_limit' => 'integer|min:0',
        'server_slots' => 'integer|min:0',
        'suspended_at' => 'nullable|date',
        'must_change_password' => 'boolean',
        'coins' => 'integer|min:0',
    ];

    /**
     * True if an admin has suspended this user's account and all of their servers.
     */
    /**
     * Whether this account still has to confirm its e-mail address before using coins and servers.
     */
    public function needsEmailVerification(): bool
    {
        return is_null($this->email_verified_at)
            && filter_var(config('mcpanel.registration.verify_email'), FILTER_VALIDATE_BOOLEAN)
            && !$this->isStaff();
    }

    public function isSuspended(): bool
    {
        return !is_null($this->suspended_at);
    }

    /**
     * Implement language verification by overriding Eloquence's gather
     * rules function.
     */
    public static function getRules(): array
    {
        $rules = parent::getRules();

        $rules['language'][] = new In(array_keys((new self())->getAvailableLanguages()));
        $rules['username'][] = new Username();

        return $rules;
    }

    /**
     * Return the user model in a format that can be passed over to Vue templates.
     */
    public function toVueObject(): array
    {
        return Collection::make($this->toArray())->except(['id', 'external_id'])
            ->merge(['identifier' => $this->identifier, 'role' => $this->effectiveRole(), 'staff' => $this->isStaff()])
            ->toArray();
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token)
    {
        Activity::event('auth:reset-password')
            ->withRequestMetadata()
            ->subject($this)
            ->log('sending password reset email');

        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Store the username as a lowercase string.
     */
    public function setUsernameAttribute(string $value)
    {
        $this->attributes['username'] = mb_strtolower($value);
    }

    /**
     * Return a concatenated result for the accounts full name.
     */
    public function getNameAttribute(): string
    {
        return trim($this->name_first . ' ' . $this->name_last);
    }

    /**
     * Returns all servers that a user owns.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Pterodactyl\Models\Server, $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'owner_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Pterodactyl\Models\ApiKey, $this>
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class)
            ->where('key_type', ApiKey::TYPE_ACCOUNT);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Pterodactyl\Models\RecoveryToken, $this>
     */
    public function recoveryTokens(): HasMany
    {
        return $this->hasMany(RecoveryToken::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Pterodactyl\Models\UserSSHKey, $this>
     */
    public function sshKeys(): HasMany
    {
        return $this->hasMany(UserSSHKey::class);
    }

    /**
     * Returns all the activity logs where this user is the subject — not to
     * be confused by activity logs where this user is the _actor_.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany<\Pterodactyl\Models\ActivityLog, $this>
     */
    public function activity(): MorphToMany
    {
        return $this->morphToMany(ActivityLog::class, 'subject', 'activity_log_subjects');
    }

    /**
     * Returns all the servers that a user can access by way of being the owner of the
     * server, or because they are assigned as a subuser for that server.
     *
     * @return \Illuminate\Database\Eloquent\Builder<\Pterodactyl\Models\Server>
     */
    public function accessibleServers(): Builder
    {
        return Server::query()
            ->select('servers.*')
            ->leftJoin('subusers', 'subusers.server_id', '=', 'servers.id')
            ->where(function (Builder $builder) {
                $builder->where('servers.owner_id', $this->id)->orWhere('subusers.user_id', $this->id);
            })
            ->groupBy('servers.id');
    }
}
