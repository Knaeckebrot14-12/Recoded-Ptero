<?php

namespace Pterodactyl\Providers;

use Psr\Log\LoggerInterface as Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * An array of configuration keys to override with database values
     * if they exist.
     */
    protected array $keys = [
        'app:name',
        'app:locale',
        'recaptcha:enabled',
        'recaptcha:secret_key',
        'recaptcha:website_key',
        'pterodactyl:guzzle:timeout',
        'pterodactyl:guzzle:connect_timeout',
        'pterodactyl:console:count',
        'pterodactyl:console:frequency',
        'pterodactyl:auth:2fa_required',
        'pterodactyl:client_features:allocations:enabled',
        'pterodactyl:client_features:allocations:range_start',
        'pterodactyl:client_features:allocations:range_end',
        'coins:linkvertise:user_id',
        'coins:linkvertise:reward',
        'coins:linkvertise:daily_limit',
        'coins:afk:reward_per_minute',
        'coins:afk:ad_slot_html',
        'coins:shop:memory_unit_mib',
        'coins:shop:memory_price',
        'coins:shop:disk_unit_mib',
        'coins:shop:disk_price',
        'coins:shop:cpu_unit_percent',
        'coins:shop:cpu_price',
        'coins:shop:backup_price',
        'coins:shop:slot_price',
        'coins:server:monthly_price',
        'coins:server:memory',
        'coins:server:disk',
        'coins:server:cpu',
        'coins:server:backups',
        'coins:server:suspension_grace_days',
        'coins:server:count_towards_pool',
        'coins:server:cancellation_refund_percent',
        'coins:daily:reward',
        'coins:daily:streak_bonus',
        'coins:daily:streak_max',
        'coins:referral:referrer_reward',
        'coins:referral:referred_bonus',
        'mcpanel:auto_update',
        'mcpanel:registration:verify_email',
        'mcpanel:registration:max_accounts_per_ip',
        'mcpanel:discord:enabled',
        'mcpanel:discord:client_id',
        'mcpanel:discord:client_secret',
        'mcpanel:discord:allow_registration',
        'mcpanel:status_page:enabled',
        'mcpanel:maintenance:mode',
        'mcpanel:maintenance:message',
        'mcpanel:monitoring:discord_webhook',
        'mcpanel:monitoring:notify_offline',
        'mcpanel:monitoring:notify_tickets',
        'mcpanel:monitoring:notify_registrations',
        'mcpanel:monitoring:disk_percent',
        'mcpanel:monitoring:memory_percent',
        'mcpanel:abuse:enabled',
        'mcpanel:abuse:cpu_percent',
        'mcpanel:abuse:cpu_minutes',
        'mcpanel:abuse:miner_enabled',
        'mcpanel:abuse:miner_keywords',
        'mcpanel:abuse:network_enabled',
        'mcpanel:abuse:network_mib_per_min',
        'mcpanel:abuse:network_minutes',
        'mcpanel:abuse:notify_discord',
        'mcpanel:abuse:notify_push',
        'mcpanel:branding:accent',
        'mcpanel:branding:background',
        'mcpanel:branding:logo',
        'mcpanel:branding:favicon',
        'mcpanel:branding:default_theme',
        'mcpanel:subdomains:enabled',
        'mcpanel:subdomains:cloudflare_token',
        'mcpanel:subdomains:domains',
        'mcpanel:push:public_key',
        'mcpanel:push:private_key',
        'mcpanel:roles:permissions',
        'mcpanel:phpmyadmin:enabled',
        'mcpanel:sleep:enabled',
        'mcpanel:sleep:minutes',
        'mcpanel:ip_lockout:enabled',
        'mcpanel:ip_lockout:max_attempts',
        'mcpanel:ip_lockout:window_minutes',
        'mcpanel:ip_lockout:block_minutes',
        'mcpanel:ip_lockout:block_minutes_2',
        'mcpanel:ip_lockout:block_minutes_3',
        'mcpanel:ip_lockout:allowlist',
    ];

    /**
     * Keys specific to the mail driver that are only grabbed from the database
     * when using the SMTP driver.
     */
    protected array $emailKeys = [
        'mail:mailers:smtp:host',
        'mail:mailers:smtp:port',
        'mail:mailers:smtp:encryption',
        'mail:mailers:smtp:username',
        'mail:mailers:smtp:password',
        'mail:from:address',
        'mail:from:name',
    ];

    /**
     * Keys that are encrypted and should be decrypted when set in the
     * configuration array.
     */
    protected static array $encrypted = [
        'mail:mailers:smtp:password',
        'mcpanel:discord:client_secret',
        'mcpanel:monitoring:discord_webhook',
        'mcpanel:subdomains:cloudflare_token',
        'mcpanel:push:private_key',
    ];

    /**
     * Boot the service provider.
     */
    public function boot(ConfigRepository $config, Encrypter $encrypter, Log $log, SettingsRepositoryInterface $settings): void
    {
        // Only set the email driver settings from the database if we
        // are configured using SMTP as the driver.
        if ($config->get('mail.default') === 'smtp') {
            $this->keys = array_merge($this->keys, $this->emailKeys);
        }

        try {
            $values = $settings->all()->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->value];
            })->toArray();
        } catch (QueryException $exception) {
            $log->notice('A query exception was encountered while trying to load settings from the database: ' . $exception->getMessage());

            return;
        }

        foreach ($this->keys as $key) {
            $value = array_get($values, 'settings::' . $key, $config->get(str_replace(':', '.', $key)));
            if (in_array($key, self::$encrypted)) {
                try {
                    $value = $encrypter->decrypt($value);
                } catch (DecryptException $exception) {
                }
            }

            switch (strtolower($value)) {
                case 'true':
                case '(true)':
                    $value = true;
                    break;
                case 'false':
                case '(false)':
                    $value = false;
                    break;
                case 'empty':
                case '(empty)':
                    $value = '';
                    break;
                case 'null':
                case '(null)':
                    $value = null;
            }

            $config->set(str_replace(':', '.', $key), $value);
        }
    }

    public static function getEncryptedKeys(): array
    {
        return self::$encrypted;
    }
}
