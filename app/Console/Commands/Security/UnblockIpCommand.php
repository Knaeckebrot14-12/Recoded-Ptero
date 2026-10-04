<?php

namespace Pterodactyl\Console\Commands\Security;

use Illuminate\Console\Command;
use Pterodactyl\Services\Security\IpLockoutService;

/**
 * For the case that the whole team is locked out of the login (the admin area itself is never
 * affected by a block, but staff may have to sign in first).
 */
class UnblockIpCommand extends Command
{
    protected $description = 'Lift the automatic login block of an IP address or a browser ("d:...", shown on the Blocked IPs page).';

    protected $signature = 'p:security:unblock-ip {ip : The IP address (or browser id "d:<hash>") to unblock}';

    public function handle(IpLockoutService $lockout): int
    {
        $ip = (string) $this->argument('ip');
        if (filter_var($ip, FILTER_VALIDATE_IP) === false && !preg_match('/^d:[a-f0-9]{40}$/', $ip)) {
            $this->error('That is not a valid IP address or browser id.');

            return self::FAILURE;
        }

        $lifted = $lockout->unblock($ip, null);
        $this->info($lifted > 0 ? "Unblocked {$ip}." : "{$ip} was not blocked.");

        return self::SUCCESS;
    }
}
