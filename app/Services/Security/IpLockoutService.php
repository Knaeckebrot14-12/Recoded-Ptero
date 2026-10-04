<?php

namespace Pterodactyl\Services\Security;

use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Models\IpBlock;
use Illuminate\Support\Facades\Cache;
use Pterodactyl\Models\LoginFailure;
use Symfony\Component\HttpFoundation\IpUtils;
use Pterodactyl\Services\Notifications\TeamAlerts;
use Pterodactyl\Services\Notifications\DiscordWebhook;

/**
 * Blocks IP addresses that fail too many logins. Failed logins and 2FA codes are counted per IP in
 * the login_failures table; when an IP reaches the limit inside the time window it gets a row in
 * ip_blocks, and the "ip.lockout" middleware then refuses every request to the auth endpoints from
 * that IP until the block ends. Repeated blocks get longer.
 *
 * IPv4 addresses are counted one by one, IPv6 addresses as their /64 (see bucket()).
 *
 * Safety rules: private, loopback and link-local addresses are never blocked (behind a reverse proxy
 * that is not trusted, every visitor would share one such address and a single typo-prone user
 * would lock out everybody), and neither is anything on the staff allowlist.
 */
class IpLockoutService
{
    /** Name of the browser cookie that tells one browser from another (random, set on the sign-in pages). */
    public const DEVICE_COOKIE = 'mcpanel_device';

    /**
     * Cloudflare's published address ranges (cloudflare.com/ips). A request that arrives from one of
     * them comes through a shared server that serves many unrelated visitors, so such an address is
     * never blocked; the visitor's own address is taken from Cloudflare's CF-Connecting-IP header.
     */
    public const CLOUDFLARE_RANGES = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
        '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
        '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
        '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    /** Without a new block for this long, the next block is a first block again. */
    public const ESCALATION_DAYS = 7;

    /** A Discord message is only sent for an IP that wasn't blocked in the last 24 hours. */
    public const NOTIFY_QUIET_HOURS = 24;

    /** At most this many Discord messages per hour, however many IPs get blocked. */
    public const NOTIFY_PER_HOUR = 5;

    /** Failed attempts are kept this long for the staff overview. */
    public const FAILURE_RETENTION_DAYS = 7;

    /** Finished blocks are kept this long for the staff overview. */
    public const BLOCK_RETENTION_DAYS = 30;

    /**
     * Short duration for logs and messages: "45 min", "6 h", "2 d".
     */
    public static function durationLabel(int $minutes): string
    {
        return match (true) {
            $minutes >= 1440 && $minutes % 1440 === 0 => ($minutes / 1440) . ' d',
            $minutes >= 60 && $minutes % 60 === 0 => ($minutes / 60) . ' h',
            default => $minutes . ' min',
        };
    }

    public function enabled(): bool
    {
        return filter_var(config('mcpanel.ip_lockout.enabled', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function maxAttempts(): int
    {
        return max(1, (int) config('mcpanel.ip_lockout.max_attempts', 10));
    }

    public function windowMinutes(): int
    {
        return max(1, (int) config('mcpanel.ip_lockout.window_minutes', 15));
    }

    /**
     * How long the n-th block within the escalation period lasts (0 = first block).
     */
    public function durationMinutes(int $previousBlocks): int
    {
        $key = match (true) {
            $previousBlocks <= 0 => ['block_minutes', 30],
            $previousBlocks === 1 => ['block_minutes_2', 120],
            default => ['block_minutes_3', 1440],
        };

        return max(1, (int) config('mcpanel.ip_lockout.' . $key[0], $key[1]));
    }

    /**
     * @return string[] the allowlist entries (IPs and CIDR ranges)
     */
    public function allowlist(): array
    {
        return self::parseAllowlist((string) config('mcpanel.ip_lockout.allowlist', ''));
    }

    /**
     * @return string[]
     */
    public static function parseAllowlist(string $text): array
    {
        $entries = [];
        foreach (preg_split('/[\s,;]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $entry) {
            if (self::isValidEntry($entry)) {
                $entries[] = $entry;
            }
        }

        return array_values(array_unique($entries));
    }

    /**
     * Whether $entry is an IP address or a CIDR range.
     */
    public static function isValidEntry(string $entry): bool
    {
        $parts = explode('/', $entry);
        if (count($parts) > 2 || filter_var($parts[0], FILTER_VALIDATE_IP) === false) {
            return false;
        }
        if (count($parts) === 2) {
            $max = str_contains($parts[0], ':') ? 128 : 32;

            return ctype_digit($parts[1]) && (int) $parts[1] <= $max;
        }

        return true;
    }

    /**
     * IPv4-mapped IPv6 addresses (::ffff:1.2.3.4) are the same client as the plain IPv4 address.
     */
    public static function normalize(?string $ip): string
    {
        $ip = trim((string) $ip);
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return $ip;
        }
        if (strlen($packed) === 16 && str_starts_with($packed, str_repeat("\0", 10) . "\xff\xff")) {
            return inet_ntop(substr($packed, 12));
        }

        return inet_ntop($packed);
    }

    /**
     * What failures are counted and blocks are stored for. IPv4 addresses count individually; an IPv6
     * client normally owns a whole /64, so it would switch to the next address after every block. IPv6
     * addresses are therefore counted and blocked as their /64 (stored as the network address).
     */
    public static function bucket(?string $ip): string
    {
        $ip = self::normalize($ip);
        $packed = @inet_pton($ip);
        if ($packed === false || strlen($packed) !== 16) {
            return $ip;
        }

        return inet_ntop(substr($packed, 0, 8) . str_repeat("\0", 8));
    }

    /**
     * Private, loopback, link-local, reserved and carrier-grade NAT addresses.
     */
    public static function isPrivate(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return true;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
            || IpUtils::checkIp($ip, ['100.64.0.0/10', 'fe80::/10', 'fc00::/7', '::1/128']);
    }

    /**
     * Addresses that are never blocked: private ones and everything on the allowlist.
     */
    public function isExempt(?string $ip): bool
    {
        $ip = self::normalize($ip);
        if ($ip === '' || self::isPrivate($ip)) {
            return true;
        }

        return $this->isAllowlisted($ip);
    }

    public function isAllowlisted(?string $ip): bool
    {
        $ip = self::normalize($ip);
        $allowlist = $this->allowlist();

        return $ip !== '' && $allowlist !== [] && IpUtils::checkIp($ip, $allowlist);
    }

    /**
     * A shared server in front of the panel (Cloudflare): many unrelated visitors arrive from it.
     */
    public static function isSharedProxy(?string $ip): bool
    {
        $ip = self::normalize($ip);

        return $ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) !== false && IpUtils::checkIp($ip, self::CLOUDFLARE_RANGES);
    }

    /**
     * The address of the visitor himself, or null when it can't be told. Behind Cloudflare the address
     * the panel sees belongs to a Cloudflare server, so the visitor's own address is read from
     * CF-Connecting-IP, and only then: a request from any other address could send that header with
     * whatever it likes. Without a usable header a shared address is never used, so nobody innocent
     * is blocked by it (the browser cookie still works).
     */
    public function clientIp(Request $request): ?string
    {
        $ip = self::normalize($request->ip());
        if ($ip === '') {
            return null;
        }
        if (!self::isSharedProxy($ip)) {
            return $ip;
        }

        $real = self::normalize((string) $request->headers->get('CF-Connecting-IP', ''));
        $usable = $real !== '' && filter_var($real, FILTER_VALIDATE_IP) !== false && !self::isSharedProxy($real) && !self::isPrivate($real);

        return $usable ? $real : null;
    }

    /**
     * How the panel sees the visitor, for the settings page:
     *  real       an address that can be blocked
     *  cloudflare the visitor's own address, read from Cloudflare's header
     *  shared     a shared (Cloudflare) address without a usable header: only the browser can be blocked
     *  private    a private address (proxy not told apart): only the browser can be blocked
     *
     * @return array{mode: string, ip: string}
     */
    public function describeClient(Request $request): array
    {
        $seen = self::normalize($request->ip());
        $client = $this->clientIp($request);

        return match (true) {
            $client === null => ['mode' => 'shared', 'ip' => $seen],
            $client !== $seen => ['mode' => 'cloudflare', 'ip' => $client],
            self::isPrivate($client) => ['mode' => 'private', 'ip' => $client],
            default => ['mode' => 'real', 'ip' => $client],
        };
    }

    /**
     * The browser cookie of the request, if it has a valid one.
     */
    public static function deviceId(Request $request): ?string
    {
        $id = $request->attributes->get('mcpanel_device_id') ?? $request->cookies->get(self::DEVICE_COOKIE);

        return is_string($id) && preg_match('/^[a-f0-9]{32}$/', $id) ? $id : null;
    }

    /**
     * What a browser is stored under in the block list: "d:" + a hash of its cookie (the cookie itself
     * is never stored).
     */
    public static function deviceKey(Request $request): ?string
    {
        $id = self::deviceId($request);

        return $id === null ? null : 'd:' . substr(hash('sha256', $id), 0, 40);
    }

    public function activeBlock(?string $ip): ?IpBlock
    {
        $ip = self::bucket($ip);
        if ($ip === '') {
            return null;
        }

        return IpBlock::query()->active()->where('ip', $ip)->orderByDesc('blocked_until')->first();
    }

    /**
     * The block that applies to a request from $ip right now, or null. Never returns anything
     * while the feature is off or for exempt addresses (so allowlisting also lifts a running block).
     */
    public function blockFor(?string $ip): ?IpBlock
    {
        // Fails open: if the lookup breaks (database trouble, a table that isn't there yet), nobody is
        // locked out and the sign-in keeps working. Counting failures is guarded the same way.
        try {
            if (!$this->enabled() || $this->isExempt($ip)) {
                return null;
            }

            return $this->activeBlock($ip);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * The block that applies to this request, if any: the visitor's own address and his browser are
     * both checked. Never returns anything while the feature is off or for a request from an address on
     * the staff allowlist. Fails open like blockFor().
     */
    public function blockForRequest(Request $request): ?IpBlock
    {
        try {
            if (!$this->enabled()) {
                return null;
            }
            $client = $this->clientIp($request);
            if ($client !== null && $this->isAllowlisted($client)) {
                return null;
            }

            foreach ($this->subjects($request, $client) as $subject) {
                if ($block = $this->activeBlock($subject)) {
                    return $block;
                }
            }

            return null;
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * What this request can be blocked as: the visitor's own address (when it is known and not a
     * private one) and his browser.
     *
     * @return string[]
     */
    private function subjects(Request $request, ?string $client): array
    {
        $subjects = [];
        if ($client !== null && !self::isPrivate($client)) {
            $subjects[] = self::bucket($client);
        }
        if ($device = self::deviceKey($request)) {
            $subjects[] = $device;
        }

        return $subjects;
    }

    /**
     * Counts a failed login or 2FA attempt of this request and blocks the visitor's address and/or his
     * browser once one of them reached the limit. A shared address (Cloudflare) is never blocked.
     *
     * @return IpBlock|null the new block, if this attempt caused one
     */
    public function recordFailureForRequest(Request $request, ?string $username, string $type = 'login'): ?IpBlock
    {
        if (!$this->enabled()) {
            return null;
        }

        try {
            $client = $this->clientIp($request);
            $device = self::deviceKey($request);
            $stored = self::bucket($client ?? self::normalize($request->ip()));
            if ($stored === '' && $device === null) {
                return null;
            }

            LoginFailure::query()->create([
                'ip' => $stored !== '' ? $stored : 'unknown',
                'device' => $device,
                'username' => $username !== null && $username !== '' ? mb_substr(mb_strtolower($username), 0, 191) : null,
                'type' => $type,
            ]);

            if ($client !== null && $this->isAllowlisted($client)) {
                return null;
            }

            $block = null;
            foreach ($this->subjects($request, $client) as $subject) {
                $block ??= $this->blockSubjectIfOverLimit($subject);
            }

            return $block;
        } catch (\Throwable $exception) {
            // Counting must never make a login request fail.
            report($exception);

            return null;
        }
    }

    private function blockSubjectIfOverLimit(string $subject): ?IpBlock
    {
        if ($this->activeBlock($subject)) {
            return null;
        }

        // One at a time per subject: parallel failures must not each create their own (escalating) block.
        return Cache::lock('ip-lockout:' . $subject, 10)->block(5, fn () => $this->blockIfOverLimit($subject, str_starts_with($subject, 'd:') ? 'device' : 'ip'));
    }

    /**
     * Counts a failed login or 2FA attempt and blocks the IP once it reached the limit.
     *
     * @return IpBlock|null the new block, if this attempt caused one
     */
    public function recordFailure(?string $ip, ?string $username, string $type = 'login'): ?IpBlock
    {
        if (!$this->enabled()) {
            return null;
        }

        try {
            $client = self::normalize($ip);
            $ip = self::bucket($client);
            if ($ip === '') {
                return null;
            }

            LoginFailure::query()->create([
                'ip' => $ip,
                'username' => $username !== null && $username !== '' ? mb_substr(mb_strtolower($username), 0, 191) : null,
                'type' => $type,
            ]);

            if ($this->isExempt($client) || $this->activeBlock($ip)) {
                return null;
            }

            // One at a time per IP: parallel failures must not each create their own (escalating) block.
            return Cache::lock('ip-lockout:' . $ip, 10)->block(5, fn () => $this->blockIfOverLimit($ip));
        } catch (\Throwable $exception) {
            // Counting must never make a login request fail.
            report($exception);

            return null;
        }
    }

    /**
     * Creates the block if the failures since the IP's latest block (or inside the window) reached the limit.
     * Runs inside a per-IP lock.
     */
    private function blockIfOverLimit(string $ip, string $column = 'ip'): ?IpBlock
    {
        // Another request may have blocked the IP while this one waited for the lock.
        if ($this->activeBlock($ip)) {
            return null;
        }

        // Only failures after the latest block count: when a block is shorter than the window, the
        // failures that caused it must not block again with the first typo afterwards.
        // $column is "ip" for an address and "device" for a browser (then $ip holds the "d:" key).
        $since = now()->subMinutes($this->windowMinutes());
        $lastBlock = IpBlock::query()->where('ip', $ip)->orderByDesc('id')->first();
        $window = LoginFailure::query()->where($column, $ip)->where('created_at', '>=', $since);
        if ($lastBlock && $lastBlock->created_at->greaterThanOrEqualTo($since)) {
            $window = LoginFailure::query()->where($column, $ip)->where('created_at', '>', $lastBlock->created_at);
        }
        $count = (clone $window)->count();
        if ($count < $this->maxAttempts()) {
            return null;
        }

        $previous = IpBlock::query()
            ->where('ip', $ip)
            ->where('reason', IpBlock::REASON_AUTO)
            ->where('created_at', '>=', now()->subDays(self::ESCALATION_DAYS))
            ->count();

        $usernames = (clone $window)->whereNotNull('username')->distinct()->limit(25)->pluck('username')->all();

        /** @var IpBlock $block */
        $block = IpBlock::query()->create([
            'ip' => $ip,
            'reason' => IpBlock::REASON_AUTO,
            'failures' => $count,
            'usernames' => implode("\n", $usernames),
            'blocked_until' => now()->addMinutes($this->durationMinutes($previous)),
        ]);

        $this->notifyTeam($block);

        return $block;
    }

    /**
     * A successful login clears the failures of the account that was used, but not those against
     * other usernames: someone who guesses passwords for many accounts and logs in to his own
     * between the attempts must not get a fresh start.
     *
     * @param string[] $usernames the names the account can be typed in as (username, e-mail)
     */
    public function recordSuccess(?string $ip, array $usernames): void
    {
        try {
            $ip = self::bucket($ip);
            $usernames = array_values(array_filter(array_map(fn ($name) => mb_strtolower((string) $name), $usernames)));
            if ($ip === '' || $usernames === []) {
                return;
            }

            LoginFailure::query()->where('ip', $ip)->whereIn('username', $usernames)->delete();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * A successful login of this request clears the failures of the account that was used, for the
     * visitor's address and for his browser.
     *
     * @param string[] $usernames
     */
    public function recordSuccessForRequest(Request $request, array $usernames): void
    {
        try {
            $client = $this->clientIp($request);
            $usernames = array_values(array_filter(array_map(fn ($name) => mb_strtolower((string) $name), $usernames)));
            if ($usernames === []) {
                return;
            }

            $query = LoginFailure::query()->whereIn('username', $usernames);
            $device = self::deviceKey($request);
            $ip = $client !== null ? self::bucket($client) : '';
            $query->where(function ($q) use ($ip, $device) {
                if ($ip !== '') {
                    $q->orWhere('ip', $ip);
                }
                if ($device !== null) {
                    $q->orWhere('device', $device);
                }
            });
            if ($ip !== '' || $device !== null) {
                $query->delete();
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Blocks an IP by hand. An existing block of the IP is replaced instead of stacked.
     *
     * @throws \InvalidArgumentException when the address can never be blocked
     */
    public function blockManually(string $ip, int $minutes, ?User $actor): IpBlock
    {
        $ip = self::normalize($ip);
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new \InvalidArgumentException('invalid');
        }
        if ($this->isExempt($ip)) {
            throw new \InvalidArgumentException('exempt');
        }
        $ip = self::bucket($ip);

        $until = now()->addMinutes(max(1, $minutes));
        if ($existing = $this->activeBlock($ip)) {
            $existing->update(['blocked_until' => $until, 'reason' => IpBlock::REASON_MANUAL, 'created_by' => $actor?->id]);

            return $existing;
        }

        return IpBlock::query()->create([
            'ip' => $ip,
            'reason' => IpBlock::REASON_MANUAL,
            'failures' => 0,
            'blocked_until' => $until,
            'created_by' => $actor?->id,
        ]);
    }

    /**
     * Lifts every running block of the IP and forgets its recent failures, so that the next typo
     * doesn't block it again at once.
     *
     * @return int number of blocks lifted
     */
    public function unblock(string $ip, ?User $actor): int
    {
        // An address, or a browser ("d:<hash>").
        $isDevice = str_starts_with($ip, 'd:');
        $ip = $isDevice ? $ip : self::bucket($ip);

        $lifted = IpBlock::query()->active()->where('ip', $ip)->update([
            'unblocked_at' => now(),
            'unblocked_by' => $actor?->id,
        ]);
        LoginFailure::query()->where($isDevice ? 'device' : 'ip', $ip)->delete();

        return $lifted;
    }

    /**
     * Deletes old failures and finished blocks.
     *
     * @return array{failures: int, blocks: int}
     */
    public function cleanup(): array
    {
        return [
            'failures' => LoginFailure::query()->where('created_at', '<', now()->subDays(self::FAILURE_RETENTION_DAYS))->delete(),
            'blocks' => IpBlock::query()
                ->where('created_at', '<', now()->subDays(self::BLOCK_RETENTION_DAYS))
                ->where(function ($query) {
                    $query->where('blocked_until', '<', now())->orWhereNotNull('unblocked_at');
                })
                ->delete(),
        ];
    }

    /**
     * Tells the team's Discord channel about an automatic block, but only for the first block of an
     * IP in 24 hours and at most 5 times per hour overall, so a botnet can't flood the channel.
     */
    private function notifyTeam(IpBlock $block): void
    {
        if (!DiscordWebhook::isValidUrl(config('mcpanel.monitoring.discord_webhook'))) {
            return;
        }

        $seenRecently = IpBlock::query()
            ->where('ip', $block->ip)
            ->where('reason', IpBlock::REASON_AUTO)
            ->where('id', '!=', $block->id)
            ->where('created_at', '>=', now()->subHours(self::NOTIFY_QUIET_HOURS))
            ->exists();
        if ($seenRecently) {
            return;
        }

        $sentLastHour = IpBlock::query()->where('notified_at', '>=', now()->subHour())->count();
        if ($sentLastHour >= self::NOTIFY_PER_HOUR) {
            return;
        }

        $block->update(['notified_at' => now()]);
        TeamAlerts::ipBlocked($block);
    }
}
