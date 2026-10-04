<?php

namespace Pterodactyl\Http\Middleware;

use Illuminate\Http\Request;
use Pterodactyl\Models\IpBlock;
use Illuminate\Support\Facades\Cookie;
use Pterodactyl\Services\Security\IpLockoutService;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Refuses the sign-in endpoints (login, 2FA checkpoint, passkey login, register, forgot/reset
 * password) with a 429 while the visitor is blocked for too many failed logins, see
 * IpLockoutService. The block applies to the visitor's own address and/or his browser, never to a
 * shared address such as a Cloudflare server. Only requests that send data are refused: the pages
 * themselves stay reachable, as does everything for people who are already logged in, because
 * blocking the whole panel for everybody behind a shared IP would be too harsh.
 *
 * On the sign-in pages every browser gets a random cookie (a security cookie, it holds nothing but a
 * random number), so a browser stays recognisable when its IP address changes.
 *
 * The message says nothing about accounts.
 */
class CheckIpLockout
{
    /** One year, in minutes. */
    private const COOKIE_MINUTES = 525600;

    public function __construct(private IpLockoutService $lockout)
    {
    }

    public function handle(Request $request, \Closure $next): mixed
    {
        $this->ensureDeviceCookie($request);

        if ($request->isMethodSafe() || $request->user() !== null) {
            return $next($request);
        }

        $block = $this->lockout->blockForRequest($request);
        if ($block === null) {
            return $next($request);
        }

        throw $this->refusal($block);
    }

    /**
     * Gives a browser without a (valid) cookie a new one. The new id is also put on the request so that
     * a failed login in this very request is already counted for it.
     */
    private function ensureDeviceCookie(Request $request): void
    {
        try {
            if (!$this->lockout->enabled() || IpLockoutService::deviceId($request) !== null) {
                return;
            }

            $id = bin2hex(random_bytes(16));
            $request->attributes->set('mcpanel_device_id', $id);
            Cookie::queue(Cookie::make(IpLockoutService::DEVICE_COOKIE, $id, self::COOKIE_MINUTES, '/', null, $request->isSecure(), true, false, 'lax'));
        } catch (\Throwable $exception) {
            // The cookie is an extra; the sign-in must work without it.
            report($exception);
        }
    }

    private function refusal(IpBlock $block): HttpException
    {
        $seconds = max(1, (int) now()->diffInSeconds($block->blocked_until, false));
        $minutes = max(1, (int) ceil($seconds / 60));

        return new HttpException(
            429,
            trans('auth.ip_blocked', ['minutes' => $minutes]),
            null,
            ['Retry-After' => (string) $seconds]
        );
    }
}
