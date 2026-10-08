<?php

use Pterodactyl\Enum\ResourceLimit;
use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Api\Client;
use Pterodactyl\Http\Middleware\Activity\ServerSubject;
use Pterodactyl\Http\Middleware\Activity\AccountSubject;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Pterodactyl\Http\Middleware\EnsurePasswordHasBeenChanged;
use Pterodactyl\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client
|
*/
Route::get('/', [Client\ClientController::class, 'index'])->name('api:client.index');
Route::get('/permissions', [Client\ClientController::class, 'permissions']);
Route::get('/announcements', [Client\AnnouncementController::class, 'index'])->name('api:client.announcements');
Route::get('/self-service/servers', [Client\SelfServiceServerController::class, 'index'])->name('api:client.self-service.servers.index');
Route::post('/self-service/servers', [Client\SelfServiceServerController::class, 'store'])->middleware('verified.email')->name('api:client.self-service.servers');
Route::delete('/self-service/servers/{server}', [Client\SelfServiceServerController::class, 'destroy'])->name('api:client.self-service.servers.destroy');

Route::get('/coins', [Client\CoinsController::class, 'index'])->name('api:client.coins');
Route::get('/coins/transactions', [Client\CoinsController::class, 'transactions'])->name('api:client.coins.transactions');
Route::post('/coins/linkvertise', [Client\LinkvertiseController::class, 'store'])->middleware('verified.email')->name('api:client.coins.linkvertise');
Route::post('/coins/voucher', [Client\CoinExtrasController::class, 'redeemVoucher'])->middleware('verified.email')->name('api:client.coins.voucher');
Route::post('/coins/daily', [Client\CoinExtrasController::class, 'claimDaily'])->middleware('verified.email')->name('api:client.coins.daily');
Route::post('/coins/afk',[Client\AfkController::class, 'tick'])->middleware('verified.email')->name('api:client.coins.afk');
Route::post('/coins/shop/resource', [Client\ShopController::class, 'purchaseResource'])->middleware('verified.email')->name('api:client.coins.shop.resource');
Route::post('/coins/shop/server', [Client\ShopController::class, 'purchaseServer'])->middleware('verified.email')->name('api:client.coins.shop.server');

Route::get('/ownership-requests', [Client\OwnershipRequestController::class, 'index']);
Route::post('/ownership-requests/{id}/accept', [Client\OwnershipRequestController::class, 'accept'])->whereNumber('id')->middleware('throttle:10,1,ownership');
Route::post('/ownership-requests/{id}/decline', [Client\OwnershipRequestController::class, 'decline'])->whereNumber('id')->middleware('throttle:10,1,ownership');

Route::get('/tickets', [Client\TicketController::class, 'index'])->name('api:client.tickets');
Route::post('/tickets', [Client\TicketController::class, 'store'])->name('api:client.tickets.store');
Route::get('/tickets/servers', [Client\TicketController::class, 'servers'])->name('api:client.tickets.servers');
Route::get('/tickets/{ticket}', [Client\TicketController::class, 'view'])->whereNumber('ticket')->name('api:client.tickets.view');
Route::post('/tickets/{ticket}/reply', [Client\TicketController::class, 'reply'])->whereNumber('ticket')->name('api:client.tickets.reply');
Route::post('/tickets/{ticket}/close', [Client\TicketController::class, 'close'])->whereNumber('ticket')->name('api:client.tickets.close');
Route::post('/tickets/{ticket}/rate', [Client\TicketController::class, 'rate'])->whereNumber('ticket')->name('api:client.tickets.rate');
Route::post('/tickets/{ticket}/reopen', [Client\TicketController::class, 'reopen'])->whereNumber('ticket')->name('api:client.tickets.reopen');

Route::prefix('/account')->middleware(AccountSubject::class)->group(function () {
    Route::prefix('/')->withoutMiddleware([RequireTwoFactorAuthentication::class, EnsurePasswordHasBeenChanged::class])->group(function () {
        Route::get('/', [Client\AccountController::class, 'index'])->name('api:client.account');
        Route::get('/two-factor', [Client\TwoFactorController::class, 'index']);
        Route::post('/two-factor', [Client\TwoFactorController::class, 'store']);
        Route::post('/two-factor/disable', [Client\TwoFactorController::class, 'delete']);
    });

    Route::post('/verify-email', [Client\EmailVerificationController::class, 'resend'])
        ->name('api:client.account.verify-email');

    Route::get('/push', [Client\PushController::class, 'index']);
    Route::post('/push', [Client\PushController::class, 'store'])->middleware('throttle:20,1,push');
    Route::delete('/push', [Client\PushController::class, 'delete']);
    Route::post('/push/test', [Client\PushController::class, 'test'])->middleware('throttle:5,1,push-test');
    Route::delete('/discord', [Client\AccountController::class, 'unlinkDiscord'])
        ->name('api:client.account.discord.unlink');

    Route::put('/email', [Client\AccountController::class, 'updateEmail'])
        ->middleware('throttle')
        ->name('api:client.account.update-email');
    Route::get('/languages', [Client\AccountController::class, 'languages'])
        ->withoutMiddleware(EnsurePasswordHasBeenChanged::class)
        ->name('api:client.account.languages');
    Route::put('/language', [Client\AccountController::class, 'updateLanguage'])
        ->withoutMiddleware(EnsurePasswordHasBeenChanged::class)
        ->name('api:client.account.update-language');
    Route::put('/password', [Client\AccountController::class, 'updatePassword'])
        ->withoutMiddleware(EnsurePasswordHasBeenChanged::class)
        ->name('api:client.account.update-password');

    Route::get('/activity', Client\ActivityLogController::class)->name('api:client.account.activity');

    Route::get('/api-keys', [Client\ApiKeyController::class, 'index']);
    Route::post('/api-keys', [Client\ApiKeyController::class, 'store']);
    Route::delete('/api-keys/{identifier}', [Client\ApiKeyController::class, 'delete']);

    Route::prefix('/ssh-keys')->group(function () {
        Route::get('/', [Client\SSHKeyController::class, 'index']);
        Route::post('/', [Client\SSHKeyController::class, 'store']);
        Route::post('/remove', [Client\SSHKeyController::class, 'delete']);
    });

    // Passkeys (WebAuthn). Adding and removing ask for the current password, which is checked on
    // every call, so they share a tight named throttle against password guessing.
    Route::prefix('/passkeys')->group(function () {
        Route::get('/', [Client\PasskeyController::class, 'index']);
        Route::post('/options', [Client\PasskeyController::class, 'options'])->middleware('throttle:10,1,passkey-manage');
        Route::post('/', [Client\PasskeyController::class, 'store'])->middleware('throttle:10,1,passkey-manage');
        Route::delete('/{id}', [Client\PasskeyController::class, 'delete'])->whereNumber('id')->middleware('throttle:10,1,passkey-manage');
    });
});

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client/servers/{server}
|
*/
Route::group([
    'prefix' => '/servers/{server}',
    'middleware' => [
        ServerSubject::class,
        AuthenticateServerAccess::class,
        ResourceBelongsToServer::class,
    ],
], function () {
    Route::get('/', [Client\Servers\ServerController::class, 'index'])->name('api:client:server.view');
    Route::middleware([ResourceLimit::Websocket->middleware()])
        ->get('/websocket', Client\Servers\WebsocketController::class)
        ->name('api:client:server.ws');
    Route::get('/resources', Client\Servers\ResourceUtilizationController::class)->name('api:client:server.resources');
    Route::get('/activity', Client\Servers\ActivityLogController::class)->name('api:client:server.activity');

    Route::post('/command', [Client\Servers\CommandController::class, 'index']);
    Route::post('/power', [Client\Servers\PowerController::class, 'index']);

    Route::group(['prefix' => '/databases'], function () {
        Route::get('/', [Client\Servers\DatabaseController::class, 'index']);
        Route::middleware([ResourceLimit::Database->middleware()])
            ->post('/', [Client\Servers\DatabaseController::class, 'store']);
        Route::post('/{database}/rotate-password', [Client\Servers\DatabaseController::class, 'rotatePassword']);
        Route::post('/{database}/manager', [Client\Servers\DatabaseController::class, 'manager'])
            ->middleware('throttle:20,1,phpmyadmin');
        Route::delete('/{database}', [Client\Servers\DatabaseController::class, 'delete']);
    });

    Route::group(['prefix' => '/files'], function () {
        Route::get('/list', [Client\Servers\FileController::class, 'directory']);
        Route::get('/contents', [Client\Servers\FileController::class, 'contents']);
        Route::get('/download', [Client\Servers\FileController::class, 'download']);
        Route::put('/rename', [Client\Servers\FileController::class, 'rename']);
        Route::post('/copy', [Client\Servers\FileController::class, 'copy']);
        Route::post('/write', [Client\Servers\FileController::class, 'write']);
        Route::post('/compress', [Client\Servers\FileController::class, 'compress']);
        Route::post('/decompress', [Client\Servers\FileController::class, 'decompress']);
        Route::post('/delete', [Client\Servers\FileController::class, 'delete']);
        Route::post('/create-folder', [Client\Servers\FileController::class, 'create']);
        Route::post('/chmod', [Client\Servers\FileController::class, 'chmod']);
        Route::middleware([ResourceLimit::FilePull->middleware()])
            ->post('/pull', [Client\Servers\FileController::class, 'pull']);
        Route::get('/upload', Client\Servers\FileUploadController::class);
    });

    Route::group(['prefix' => '/schedules'], function () {
        Route::get('/', [Client\Servers\ScheduleController::class, 'index']);
        Route::middleware([ResourceLimit::Schedule->middleware()])
            ->post('/', [Client\Servers\ScheduleController::class, 'store']);
        Route::get('/{schedule}', [Client\Servers\ScheduleController::class, 'view']);
        Route::post('/{schedule}', [Client\Servers\ScheduleController::class, 'update']);
        Route::post('/{schedule}/execute', [Client\Servers\ScheduleController::class, 'execute']);
        Route::delete('/{schedule}', [Client\Servers\ScheduleController::class, 'delete']);

        Route::post('/{schedule}/tasks', [Client\Servers\ScheduleTaskController::class, 'store']);
        Route::post('/{schedule}/tasks/{task}', [Client\Servers\ScheduleTaskController::class, 'update']);
        Route::delete('/{schedule}/tasks/{task}', [Client\Servers\ScheduleTaskController::class, 'delete']);
    });

    Route::group(['prefix' => '/network'], function () {
        Route::get('/allocations', [Client\Servers\NetworkAllocationController::class, 'index']);
        Route::middleware([ResourceLimit::Allocation->middleware()])
            ->post('/allocations', [Client\Servers\NetworkAllocationController::class, 'store']);
        Route::post('/allocations/{allocation}', [Client\Servers\NetworkAllocationController::class, 'update']);
        Route::post('/allocations/{allocation}/primary', [Client\Servers\NetworkAllocationController::class, 'setPrimary']);
        Route::delete('/allocations/{allocation}', [Client\Servers\NetworkAllocationController::class, 'delete']);
    });

    Route::group(['prefix' => '/users'], function () {
        Route::get('/', [Client\Servers\SubuserController::class, 'index']);
        Route::middleware([ResourceLimit::Subuser->middleware()])
            ->post('/', [Client\Servers\SubuserController::class, 'store']);
        Route::get('/{user}', [Client\Servers\SubuserController::class, 'view']);
        Route::post('/{user}', [Client\Servers\SubuserController::class, 'update']);
        Route::delete('/{user}', [Client\Servers\SubuserController::class, 'delete']);
    });

    Route::get('/players', [Client\Servers\PlayerController::class, 'index']);
    Route::post('/players', [Client\Servers\PlayerController::class, 'action'])->middleware('throttle:30,1,players');
    Route::get('/stats', [Client\Servers\StatsController::class, 'index']);

    Route::get('/software', [Client\Servers\SoftwareController::class, 'index']);
    Route::get('/software/{type}', [Client\Servers\SoftwareController::class, 'versions'])->where('type', '[a-z]+')->middleware('throttle:30,1,software');
    Route::post('/software', [Client\Servers\SoftwareController::class, 'install'])->middleware('throttle:5,1,software-install');

    Route::get('/ownership', [Client\Servers\OwnershipController::class, 'index']);
    Route::post('/ownership', [Client\Servers\OwnershipController::class, 'store'])->middleware('throttle:10,1,ownership-offer');
    Route::delete('/ownership', [Client\Servers\OwnershipController::class, 'delete']);

    Route::get('/geyser', [Client\Servers\GeyserController::class, 'index']);
    Route::post('/geyser', [Client\Servers\GeyserController::class, 'install'])->middleware('throttle:5,1,geyser');
    Route::delete('/geyser', [Client\Servers\GeyserController::class, 'uninstall'])->middleware('throttle:5,1,geyser');

    Route::get('/subdomain', [Client\Servers\SubdomainController::class, 'index']);
    Route::put('/subdomain', [Client\Servers\SubdomainController::class, 'store'])->middleware('throttle:10,1,subdomain');
    Route::delete('/subdomain', [Client\Servers\SubdomainController::class, 'delete'])->middleware('throttle:10,1,subdomain');

    Route::group(['prefix' => '/backups'], function () {
        // Must come before the /{backup} routes, which would otherwise try to bind "auto" as a backup.
        Route::get('/auto', [Client\Servers\AutoBackupController::class, 'show']);
        Route::put('/auto', [Client\Servers\AutoBackupController::class, 'update']);
        Route::get('/', [Client\Servers\BackupController::class, 'index']);
        Route::post('/', [Client\Servers\BackupController::class, 'store']);
        Route::get('/{backup}', [Client\Servers\BackupController::class, 'view']);
        Route::get('/{backup}/download', [Client\Servers\BackupController::class, 'download']);
        Route::post('/{backup}/lock', [Client\Servers\BackupController::class, 'toggleLock']);
        Route::middleware([ResourceLimit::Backup->middleware()])
            ->post('/{backup}/restore', [Client\Servers\BackupController::class, 'restore']);
        Route::delete('/{backup}', [Client\Servers\BackupController::class, 'delete']);
    });

    Route::group(['prefix' => '/startup'], function () {
        Route::get('/', [Client\Servers\StartupController::class, 'index']);
        Route::put('/variable', [Client\Servers\StartupController::class, 'update']);
    });

    Route::group(['prefix' => '/settings'], function () {
        Route::post('/rename', [Client\Servers\SettingsController::class, 'rename']);
        Route::post('/reinstall', [Client\Servers\SettingsController::class, 'reinstall']);
        Route::put('/docker-image', [Client\Servers\SettingsController::class, 'dockerImage']);
        Route::get('/sleep', [Client\Servers\SleepController::class, 'show']);
        Route::put('/sleep', [Client\Servers\SleepController::class, 'update']);
    });
});
