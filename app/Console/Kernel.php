<?php

namespace Pterodactyl\Console;

use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\ActivityLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Console\PruneCommand;
use Pterodactyl\Repositories\Eloquent\SettingsRepository;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Pterodactyl\Services\Telemetry\TelemetryCollectionService;
use Pterodactyl\Console\Commands\Schedule\ProcessRunnableCommand;
use Pterodactyl\Console\Commands\Update\CheckForUpdatesCommand;
use Pterodactyl\Console\Commands\Server\AutoBackupCommand;
use Pterodactyl\Console\Commands\Maintenance\RenewCertificateCommand;
use Pterodactyl\Console\Commands\Server\CollectServerStatsCommand;
use Pterodactyl\Console\Commands\Server\AbuseScanCommand;
use Pterodactyl\Console\Commands\Node\MonitorNodesCommand;
use Pterodactyl\Console\Commands\Coins\ChargeCoinFundedServersCommand;
use Pterodactyl\Console\Commands\Coins\RemindCoinFundedServersCommand;
use Pterodactyl\Console\Commands\Security\CleanupLoginFailuresCommand;
use Pterodactyl\Console\Commands\Maintenance\PruneOrphanedBackupsCommand;
use Pterodactyl\Console\Commands\Maintenance\CleanServiceBackupFilesCommand;

class Kernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // https://laravel.com/docs/10.x/upgrade#redis-cache-tags
        $schedule->command('cache:prune-stale-tags')->hourly();

        // Execute scheduled commands for servers every minute, as if there was a normal cron running.
        $schedule->command(ProcessRunnableCommand::class)->everyMinute()->withoutOverlapping();
        $schedule->command(CleanServiceBackupFilesCommand::class)->daily();
        $schedule->command(ChargeCoinFundedServersCommand::class)->daily()->withoutOverlapping();
        $schedule->command(RemindCoinFundedServersCommand::class)->daily();
        // Old failed logins and finished IP blocks (see Admin -> Blocked IPs).
        $schedule->command(CleanupLoginFailuresCommand::class)->daily();
        $schedule->command(CheckForUpdatesCommand::class)->everyFiveMinutes()->withoutOverlapping();
        $schedule->command(CollectServerStatsCommand::class)->everyFiveMinutes()->withoutOverlapping();
        // Flags (never suspends) servers that look abused; the stats it reads come from the collector above.
        $schedule->command(AbuseScanCommand::class)->everyFiveMinutes()->withoutOverlapping();
        // Idempotent; also repairs database hosts that were offline when the update ran.
        $schedule->command('p:databases:fix-grants')->dailyAt('04:10')->withoutOverlapping();
        $schedule->command(MonitorNodesCommand::class)->everyMinute()->withoutOverlapping()->runInBackground();
        // "Move all servers away" (admin node page): checks running moves and starts the next ones.
        $schedule->command('p:nodes:drain')->everyMinute()->withoutOverlapping();
        // File manager trash: deletes what was deleted longer ago than allowed (mcpanel.trash.hours).
        $schedule->command('p:files:purge-trash')->hourly()->withoutOverlapping();
        $schedule->command(AutoBackupCommand::class)->everyFiveMinutes()->withoutOverlapping();
        $schedule->command(RenewCertificateCommand::class)->twiceDaily(3, 15)->withoutOverlapping();

        if (config('backups.prune_age')) {
            // Every 30 minutes, run the backup pruning command so that any abandoned backups can be deleted.
            $schedule->command(PruneOrphanedBackupsCommand::class)->everyThirtyMinutes();
        }

        if (config('activity.prune_days')) {
            $schedule->command(PruneCommand::class, ['--model' => [ActivityLog::class]])->daily();
        }

        if (config('pterodactyl.telemetry.enabled')) {
            $this->registerTelemetry($schedule);
        }
    }

    /**
     * I wonder what this does.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function registerTelemetry(Schedule $schedule): void
    {
        $settingsRepository = app()->make(SettingsRepository::class);

        $uuid = $settingsRepository->get('app:telemetry:uuid');
        if (is_null($uuid)) {
            $uuid = Uuid::uuid4()->toString();
            $settingsRepository->set('app:telemetry:uuid', $uuid);
        }

        // Calculate a fixed time to run the data push at, this will be the same time every day.
        $time = hexdec(str_replace('-', '', substr($uuid, 27))) % 1440;
        $hour = floor($time / 60);
        $minute = $time % 60;

        // Run the telemetry collector.
        $schedule->call(app()->make(TelemetryCollectionService::class))->description('Collect Telemetry')->dailyAt("$hour:$minute");
    }
}
