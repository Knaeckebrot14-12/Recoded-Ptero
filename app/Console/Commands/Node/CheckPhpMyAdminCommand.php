<?php

namespace Pterodactyl\Console\Commands\Node;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Console\Command;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Cookie\CookieValuePrefix;
use Pterodactyl\Models\DatabaseHost;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Extensions\DynamicDatabaseConnection;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Services\Databases\PhpMyAdminService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

/**
 * Used by install.sh (and handy by hand): is phpMyAdmin installed, switched on, served by the web
 * server, and does it really sign in to every database host? Prints one line per check:
 *   ok|<check>|<detail>   or   fail|<check>|<detail>   or   info|<check>|<detail>
 * The sign-in test goes through the real ticket flow (/phpmyadmin/signon.php) like a browser would.
 * Exit code 1 when one check failed.
 */
class CheckPhpMyAdminCommand extends Command
{
    protected $description = 'Checks that phpMyAdmin is installed and can sign in to every database host (used by the installer).';

    protected $signature = 'p:phpmyadmin:check
                            {--enable : Turn phpMyAdmin on when it is switched off.}
                            {--no-login : Skip the sign-in test.}';

    private bool $failed = false;

    public function __construct(private Encrypter $encrypter, private PhpMyAdminService $phpMyAdmin)
    {
        parent::__construct();
    }

    public function handle(DynamicDatabaseConnection $dynamic, SettingsRepositoryInterface $settings): int
    {
        // 1. Installed? (the Docker image carries it; older images don't)
        $dir = base_path('public/phpmyadmin');
        $missing = array_filter(['index.php', 'signon.php', 'config.inc.php'], fn ($file) => !is_file("$dir/$file"));
        if ($missing !== []) {
            $this->report(false, 'image', 'phpMyAdmin is not part of this panel version (missing: ' . implode(', ', $missing) . ')');

            return self::FAILURE;
        }
        $version = is_file("$dir/libraries/classes/Version.php") && preg_match("/VERSION = '([^']+)'/", (string) file_get_contents("$dir/libraries/classes/Version.php"), $m) ? $m[1] : '?';
        $this->report(extension_loaded('mysqli'), 'image', 'phpMyAdmin ' . $version . (extension_loaded('mysqli') ? '' : ' (the mysqli extension is missing)'));

        // 2. Switched on?
        if (!PhpMyAdminService::enabled()) {
            if ($this->option('enable')) {
                $settings->set('settings::mcpanel:phpmyadmin:enabled', 'true');
                config(['mcpanel.phpmyadmin.enabled' => true]);
                $this->report(true, 'enabled', 'was switched off, turned on');
            } else {
                $this->report(false, 'enabled', 'switched off (Admin > Settings > Advanced, or run with --enable)');

                return self::FAILURE;
            }
        } else {
            $this->report(true, 'enabled', 'on');
        }

        // 3. Served by the web server, and its internals not?
        $base = $this->baseUrl();
        $host = (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost');
        try {
            $entry = $this->client($base, $host)->get('/phpmyadmin/signon.php')->status();
            $inside = $this->client($base, $host)->get('/phpmyadmin/libraries/common.inc.php')->status();
            // Without a ticket signon.php sends the browser back to the panel (a redirect) or shows its error page.
            $this->report(in_array($entry, [200, 301, 302, 303, 307, 308, 403], true) && in_array($inside, [403, 404], true), 'web', "signon.php answers $entry, internal files $inside");
        } catch (\Throwable $exception) {
            $this->report(false, 'web', 'the web server did not answer: ' . Str::limit($exception->getMessage(), 120));
        }

        // 4. Every database host.
        $hosts = DatabaseHost::query()->orderBy('id')->get();
        if ($hosts->isEmpty()) {
            $this->line('info|hosts|no database host is set up yet');
        }
        foreach ($hosts as $databaseHost) {
            $label = sprintf('%d:%s:%d', $databaseHost->id, $databaseHost->host, $databaseHost->port);
            try {
                $dynamic->set('pmacheck', $databaseHost);
                DB::connection('pmacheck')->getPdo();
            } catch (\Throwable $exception) {
                $this->report(false, 'host', "$label|the panel cannot log in: " . Str::limit($exception->getMessage(), 140));
                DB::purge('pmacheck');
                continue;
            } finally {
                DB::purge('pmacheck');
            }

            $error = $this->mysqliError($databaseHost);
            if ($error !== null) {
                $this->report(false, 'host', "$label|phpMyAdmin's connection (mysqli) fails: $error");
                continue;
            }
            if ($this->option('no-login')) {
                $this->report(true, 'host', "$label|database reachable");
                continue;
            }

            $error = $this->loginTest($databaseHost, $base, $host);
            $this->report($error === null, 'host', $label . '|' . ($error ?? 'signs in to phpMyAdmin'));
        }

        return $this->failed ? self::FAILURE : self::SUCCESS;
    }

    private function report(bool $ok, string $check, string $detail): void
    {
        $this->failed = $this->failed || !$ok;
        $this->line(($ok ? 'ok' : 'fail') . '|' . $check . '|' . str_replace("\n", ' ', $detail));
    }

    /**
     * The panel's own nginx, reached from inside the container (http, or https when it redirects).
     */
    private function baseUrl(): string
    {
        $host = (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost');
        try {
            $response = $this->client('http://127.0.0.1', $host)->get('/phpmyadmin/signon.php');
            if (in_array($response->status(), [301, 302, 308], true) && str_starts_with((string) $response->header('Location'), 'https://')) {
                return 'https://127.0.0.1';
            }
        } catch (\Throwable) {
            // The caller reports an unreachable web server.
        }

        return 'http://127.0.0.1';
    }

    private function client(string $base, string $host, ?CookieJar $jar = null): PendingRequest
    {
        $options = ['verify' => false, 'http_errors' => false, 'allow_redirects' => false];
        if ($jar) {
            $options['cookies'] = $jar;
        }

        return Http::withOptions($options)->withHeaders(['Host' => $host])->timeout(25)->baseUrl($base);
    }

    /**
     * phpMyAdmin connects with mysqli (the panel itself with PDO), so test that driver too.
     */
    private function mysqliError(DatabaseHost $host): ?string
    {
        if (!extension_loaded('mysqli')) {
            return 'the mysqli extension is missing';
        }

        try {
            mysqli_report(MYSQLI_REPORT_OFF);
            $connection = @new \mysqli($host->host, $host->username, $this->encrypter->decrypt($host->password), '', (int) $host->port);
            if ($connection->connect_errno) {
                return Str::limit($connection->connect_error, 140);
            }
            $connection->close();
        } catch (\Throwable $exception) {
            return Str::limit($exception->getMessage(), 140);
        }

        return null;
    }

    /**
     * The real sign-in: a panel session, a ticket for the host, then the redirect chain of
     * /phpmyadmin/signon.php -> index.php, exactly like a browser. Returns the problem, or null.
     */
    private function loginTest(DatabaseHost $host, string $base, string $hostHeader): ?string
    {
        try {
            // A panel session that exists only for this test; the ticket is bound to it.
            $sessionId = Str::random(40);
            $request = Request::create('/');
            $store = app('session')->driver();
            $store->setId($sessionId);
            $request->setLaravelSession($store);

            $url = $this->phpMyAdmin->launchForHost($host, $request);
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $ticket = (string) ($query['ticket'] ?? '');

            $name = (string) config('session.cookie');
            $cookie = $this->encrypter->encrypt(CookieValuePrefix::create($name, $this->encrypter->getKey()) . $sessionId, false);
            $jar = new CookieJar();
            $jar->setCookie(new SetCookie(['Name' => $name, 'Value' => rawurlencode($cookie), 'Domain' => '127.0.0.1', 'Path' => '/', 'HttpOnly' => true]));

            // Redirects are followed by hand: signon.php redirects to the panel's public address, which
            // must not be called from in here (it may not even be reachable); only path and query count.
            $path = '/phpmyadmin/signon.php?ticket=' . $ticket;
            for ($hop = 0; $hop < 6; ++$hop) {
                $response = $this->client($base, $hostHeader, $jar)->get($path);
                $location = (string) $response->header('Location');
                if ($response->status() < 300 || $response->status() >= 400 || $location === '') {
                    break;
                }
                $target = parse_url($location);
                $path = ($target['path'] ?? '/') . (isset($target['query']) ? '?' . $target['query'] : '');
                if (!str_starts_with($path, '/')) {
                    $path = '/phpmyadmin/' . $path;
                }
            }
            $body = (string) $response->body();
            $ok = $response->status() === 200 && str_contains($body, 'pma_navigation');

            // Sign out again so no session is left behind.
            $this->client($base, $hostHeader, $jar)->get('/phpmyadmin/signon.php?logout=1');

            if ($ok) {
                return null;
            }
            if ($response->status() === 403 || str_contains($body, 'expired or was already used')) {
                return 'phpMyAdmin did not accept the sign-in ticket';
            }
            if (preg_match('/<code>([^<]{1,200})<\/code>/', $body, $m)) {
                return 'the database refused phpMyAdmin: ' . trim($m[1]);
            }

            // What the page says instead, so the problem can be read from the installer's output.
            $text = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) preg_replace('#<(script|style)\b.*?</\1>#si', '', $body))));

            return 'phpMyAdmin did not show its start page (HTTP ' . $response->status() . ($text !== '' ? ', page says: "' . Str::limit($text, 160) . '"' : '') . ')';
        } catch (\Throwable $exception) {
            return Str::limit($exception->getMessage(), 140);
        }
    }
}
