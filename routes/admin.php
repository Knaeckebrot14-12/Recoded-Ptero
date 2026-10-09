<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Admin;
use Pterodactyl\Http\Middleware\Admin\Servers\ServerInstalled;

Route::get('/', [Admin\BaseController::class, 'index'])->name('admin.index');

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/api
|
*/
Route::group(['prefix' => 'api', 'middleware' => ['owner.only']], function () {
    Route::get('/', [Admin\ApiController::class, 'index'])->name('admin.api.index');
    Route::get('/new', [Admin\ApiController::class, 'create'])->name('admin.api.new');

    Route::post('/new', [Admin\ApiController::class, 'store']);

    Route::delete('/revoke/{identifier}', [Admin\ApiController::class, 'delete'])->name('admin.api.delete');
});

/*
|--------------------------------------------------------------------------
| Announcement Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/announcements
|
*/
Route::group(['prefix' => 'maintenance', 'middleware' => ['staff:maintenance']], function () {
    Route::get('/', [Admin\MaintenanceController::class, 'index'])->name('admin.maintenance');
    Route::patch('/', [Admin\MaintenanceController::class, 'update']);
});

Route::group(['prefix' => 'announcements', 'middleware' => ['staff:announcements']], function () {
    Route::get('/', [Admin\AnnouncementController::class, 'index'])->name('admin.announcements');

    Route::post('/', [Admin\AnnouncementController::class, 'create']);
    Route::post('/{announcement:id}/toggle', [Admin\AnnouncementController::class, 'toggle'])->name('admin.announcements.toggle');
    Route::delete('/{announcement:id}', [Admin\AnnouncementController::class, 'delete'])->name('admin.announcements.delete');
});

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/locations
|
*/
Route::group(['prefix' => 'locations', 'middleware' => ['staff:locations']], function () {
    Route::get('/', [Admin\LocationController::class, 'index'])->name('admin.locations');
    Route::get('/view/{location:id}', [Admin\LocationController::class, 'view'])->name('admin.locations.view');

    Route::post('/', [Admin\LocationController::class, 'create']);
    Route::patch('/view/{location:id}', [Admin\LocationController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| Database Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/databases
|
*/
Route::group(['prefix' => 'databases', 'middleware' => ['staff:databases']], function () {
    Route::get('/', [Admin\DatabaseController::class, 'index'])->name('admin.databases');
    Route::get('/view/{host:id}', [Admin\DatabaseController::class, 'view'])->name('admin.databases.view');

    Route::post('/', [Admin\DatabaseController::class, 'create']);
    // phpMyAdmin with the host's account: admins (and the owner) only, see PhpMyAdminService.
    Route::post('/view/{host:id}/manager', [Admin\DatabaseController::class, 'manager'])
        ->name('admin.databases.manager')
        ->middleware(['admin.only', 'throttle:20,1,phpmyadmin']);
    Route::patch('/view/{host:id}', [Admin\DatabaseController::class, 'update']);
    Route::delete('/view/{host:id}', [Admin\DatabaseController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Settings Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/settings
|
*/
Route::group(['prefix' => 'settings', 'middleware' => ['owner.only']], function () {
    Route::get('/', [Admin\Settings\IndexController::class, 'index'])->name('admin.settings');
    Route::get('/mail', [Admin\Settings\MailController::class, 'index'])->name('admin.settings.mail');
    Route::get('/advanced', [Admin\Settings\AdvancedController::class, 'index'])->name('admin.settings.advanced');

    Route::get('/login', [Admin\Settings\LoginSettingsController::class, 'index'])->name('admin.settings.login');
    Route::patch('/login', [Admin\Settings\LoginSettingsController::class, 'update']);

    Route::get('/updates', [Admin\Settings\UpdateController::class, 'index'])->name('admin.settings.updates');
    Route::get('/updates/status', [Admin\Settings\UpdateController::class, 'status'])->name('admin.settings.updates.status');
    Route::post('/updates/check', [Admin\Settings\UpdateController::class, 'check'])->name('admin.settings.updates.check');
    Route::post('/updates/run', [Admin\Settings\UpdateController::class, 'run'])->name('admin.settings.updates.run');
    Route::patch('/updates/auto', [Admin\Settings\UpdateController::class, 'auto'])->name('admin.settings.updates.auto');

    Route::post('/mail/test', [Admin\Settings\MailController::class, 'test'])->name('admin.settings.mail.test');

    Route::get('/monitoring', [Admin\Settings\MonitoringController::class, 'index'])->name('admin.settings.monitoring');
    Route::patch('/monitoring', [Admin\Settings\MonitoringController::class, 'update']);
    Route::post('/monitoring/test', [Admin\Settings\MonitoringController::class, 'test'])->name('admin.settings.monitoring.test');

    Route::get('/abuse', [Admin\Settings\AbuseSettingsController::class, 'index'])->name('admin.settings.abuse');
    Route::patch('/abuse', [Admin\Settings\AbuseSettingsController::class, 'update']);

    Route::get('/ip-lockout', [Admin\Settings\IpLockoutSettingsController::class, 'index'])->name('admin.settings.iplockout');
    Route::patch('/ip-lockout', [Admin\Settings\IpLockoutSettingsController::class, 'update']);

    Route::get('/roles', [Admin\Settings\RolesController::class, 'index'])->name('admin.settings.roles');
    Route::patch('/roles', [Admin\Settings\RolesController::class, 'update']);

    Route::get('/design', [Admin\Settings\DesignController::class, 'index'])->name('admin.settings.design');
    Route::post('/design', [Admin\Settings\DesignController::class, 'update']);

    Route::get('/subdomains', [Admin\Settings\SubdomainSettingsController::class, 'index'])->name('admin.settings.subdomains');
    Route::patch('/subdomains', [Admin\Settings\SubdomainSettingsController::class, 'update']);

    Route::patch('/', [Admin\Settings\IndexController::class, 'update']);
    Route::patch('/mail', [Admin\Settings\MailController::class, 'update']);
    Route::patch('/advanced', [Admin\Settings\AdvancedController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/users
|
*/
Route::group(['prefix' => 'users'], function () {
    Route::get('/', [Admin\UserController::class, 'index'])->name('admin.users')->middleware('staff:users.view');
    Route::get('/accounts.json', [Admin\UserController::class, 'json'])->name('admin.users.json')->middleware('staff:users.view,servers.create,servers.manage');
    Route::get('/new', [Admin\UserController::class, 'create'])->name('admin.users.new')->middleware('staff:users.edit');
    Route::get('/view/{user:id}', [Admin\UserController::class, 'view'])->name('admin.users.view')->middleware('staff:users.view');

    Route::post('/new', [Admin\UserController::class, 'store'])->middleware('staff:users.edit');

    Route::patch('/view/{user:id}', [Admin\UserController::class, 'update'])->middleware('staff:users.edit,users.password');
    Route::delete('/view/{user:id}', [Admin\UserController::class, 'delete'])->name('admin.users.delete')->middleware('staff:users.delete');
    Route::post('/view/{user:id}/role', [Admin\UserController::class, 'updateRole'])->name('admin.users.role')->middleware('staff:users.roles');
    Route::post('/view/{user:id}/impersonate', [Admin\UserImpersonationController::class, 'start'])->name('admin.users.impersonate')->middleware('staff:users.impersonate');

    Route::post('/view/{user:id}/verify-email', [Admin\UserController::class, 'verifyEmail'])->name('admin.users.verify-email')->middleware('staff:users.moderate');
    Route::post('/view/{user:id}/suspend',[Admin\UserController::class, 'suspend'])->name('admin.users.suspend')->middleware('staff:users.moderate');
    Route::post('/view/{user:id}/unsuspend', [Admin\UserController::class, 'unsuspend'])->name('admin.users.unsuspend')->middleware('staff:users.moderate');
    Route::post('/view/{user:id}/coins', [Admin\UserController::class, 'adjustCoins'])->name('admin.users.coins')->middleware('staff:users.coins');
});

/*
|--------------------------------------------------------------------------
| Server Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/servers
|
*/
Route::group(['prefix' => 'servers'], function () {
    Route::get('/', [Admin\Servers\ServerController::class, 'index'])->name('admin.servers')->middleware('staff:servers.view');
    Route::get('/new', [Admin\Servers\CreateServerController::class, 'index'])->name('admin.servers.new')->middleware('staff:servers.create');
    Route::get('/bulk', [Admin\Servers\BulkActionController::class, 'index'])->name('admin.servers.bulk')->middleware('staff:servers.bulk');
    Route::get('/bulk/runs/{run}', [Admin\Servers\BulkActionController::class, 'status'])->name('admin.servers.bulk.status')->where('run', '[0-9a-f-]{36}')->middleware('staff:servers.bulk');
    Route::post('/bulk/power', [Admin\Servers\BulkActionController::class, 'power'])->name('admin.servers.bulk.power')->middleware(['staff:servers.bulk', 'throttle:5,1,bulk-actions']);
    Route::post('/bulk/message', [Admin\Servers\BulkActionController::class, 'message'])->name('admin.servers.bulk.message')->middleware(['staff:servers.bulk', 'throttle:5,1,bulk-actions']);
    Route::get('/view/{server:id}',[Admin\Servers\ServerViewController::class, 'index'])->name('admin.servers.view')->middleware('staff:servers.view');

    Route::group(['middleware' => [ServerInstalled::class]], function () {
        Route::get('/view/{server:id}/details', [Admin\Servers\ServerViewController::class, 'details'])->name('admin.servers.view.details')->middleware('staff:servers.manage');
        Route::get('/view/{server:id}/build', [Admin\Servers\ServerViewController::class, 'build'])->name('admin.servers.view.build')->middleware('staff:servers.manage');
        Route::get('/view/{server:id}/startup', [Admin\Servers\ServerViewController::class, 'startup'])->name('admin.servers.view.startup')->middleware('staff:servers.manage');
        Route::get('/view/{server:id}/database', [Admin\Servers\ServerViewController::class, 'database'])->name('admin.servers.view.database')->middleware('staff:servers.manage');
        Route::get('/view/{server:id}/mounts', [Admin\Servers\ServerViewController::class, 'mounts'])->name('admin.servers.view.mounts')->middleware('staff:servers.manage');
    });

    Route::get('/view/{server:id}/manage', [Admin\Servers\ServerViewController::class, 'manage'])->name('admin.servers.view.manage')->middleware('staff:servers.manage');
    Route::get('/view/{server:id}/delete', [Admin\Servers\ServerViewController::class, 'delete'])->name('admin.servers.view.delete')->middleware('staff:servers.delete');

    Route::post('/new', [Admin\Servers\CreateServerController::class, 'store'])->middleware('staff:servers.create');
    Route::post('/view/{server:id}/build', [Admin\ServersController::class, 'updateBuild'])->middleware('staff:servers.manage');
    Route::post('/view/{server:id}/startup', [Admin\ServersController::class, 'saveStartup'])->middleware('staff:servers.manage');
    Route::post('/view/{server:id}/database', [Admin\ServersController::class, 'newDatabase'])->middleware('staff:servers.manage');
    Route::post('/view/{server:id}/mounts', [Admin\ServersController::class, 'addMount'])->name('admin.servers.view.mounts.store')->middleware('staff:servers.manage');
    Route::post('/view/{server:id}/manage/toggle', [Admin\ServersController::class, 'toggleInstall'])->name('admin.servers.view.manage.toggle')->middleware('staff:servers.manage');
    Route::post('/view/{server:id}/manage/suspension', [Admin\ServersController::class, 'manageSuspension'])->name('admin.servers.view.manage.suspension')->middleware('staff:servers.moderate');
    Route::post('/view/{server:id}/manage/reinstall', [Admin\ServersController::class, 'reinstallServer'])->name('admin.servers.view.manage.reinstall')->middleware('staff:servers.manage');
    Route::post('/view/{server:id}/manage/transfer', [Admin\Servers\ServerTransferController::class, 'transfer'])->name('admin.servers.view.manage.transfer')->middleware('staff:servers.manage');
    Route::post('/view/{server:id}/delete', [Admin\ServersController::class, 'delete'])->middleware('staff:servers.delete');

    Route::patch('/view/{server:id}/details', [Admin\ServersController::class, 'setDetails'])->middleware('staff:servers.manage');
    Route::patch('/view/{server:id}/database', [Admin\ServersController::class, 'resetDatabasePassword'])->middleware('staff:servers.manage');

    Route::delete('/view/{server:id}/database/{database:id}/delete', [Admin\ServersController::class, 'deleteDatabase'])->name('admin.servers.view.database.delete')->middleware('staff:servers.manage');
    Route::delete('/view/{server:id}/mounts/{mount:id}', [Admin\ServersController::class, 'deleteMount'])
        ->name('admin.servers.view.mounts.delete')->middleware('staff:servers.manage');
});

/*
|--------------------------------------------------------------------------
| Node Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/nodes
|
*/
Route::group(['prefix' => 'nodes', 'middleware' => ['staff:nodes']], function () {
    Route::get('/', [Admin\Nodes\NodeController::class, 'index'])->name('admin.nodes');
    Route::get('/new', [Admin\NodesController::class, 'create'])->name('admin.nodes.new');
    Route::get('/view/{node:id}', [Admin\Nodes\NodeViewController::class, 'index'])->name('admin.nodes.view');
    Route::get('/view/{node:id}/settings', [Admin\Nodes\NodeViewController::class, 'settings'])->name('admin.nodes.view.settings');
    Route::get('/view/{node:id}/configuration', [Admin\Nodes\NodeViewController::class, 'configuration'])->name('admin.nodes.view.configuration');
    Route::get('/view/{node:id}/allocation', [Admin\Nodes\NodeViewController::class, 'allocations'])->name('admin.nodes.view.allocation');
    Route::get('/view/{node:id}/servers', [Admin\Nodes\NodeViewController::class, 'servers'])->name('admin.nodes.view.servers');
    Route::get('/view/{node:id}/system-information', Admin\Nodes\SystemInformationController::class);
    Route::get('/view/{node:id}/monitoring', [Admin\Nodes\NodeMonitorController::class, 'stats'])->name('admin.nodes.view.monitoring');
    Route::get('/monitoring', [Admin\Nodes\NodeMonitorController::class, 'overview'])->name('admin.nodes.monitoring');
    Route::post('/view/{node:id}/wings-update', [Admin\Nodes\NodeMonitorController::class, 'updateWings'])->name('admin.nodes.view.wings-update');
    Route::post('/wings-update', [Admin\Nodes\NodeMonitorController::class, 'updateAllWings'])->name('admin.nodes.wings-update');
    // "Move all servers away": moving servers also needs the permission to manage servers.
    Route::get('/view/{node:id}/drain', [Admin\Nodes\NodeDrainController::class, 'status'])->name('admin.nodes.view.drain');
    Route::post('/view/{node:id}/drain', [Admin\Nodes\NodeDrainController::class, 'start'])->name('admin.nodes.view.drain.start')->middleware('staff:servers.manage');
    Route::post('/view/{node:id}/drain/cancel', [Admin\Nodes\NodeDrainController::class, 'cancel'])->name('admin.nodes.view.drain.cancel')->middleware('staff:servers.manage');
    Route::post('/view/{node:id}/drain/back', [Admin\Nodes\NodeDrainController::class, 'back'])->name('admin.nodes.view.drain.back')->middleware('staff:servers.manage');

    Route::post('/new', [Admin\NodesController::class, 'store']);
    Route::post('/view/{node:id}/allocation', [Admin\NodesController::class, 'createAllocation']);
    Route::post('/view/{node:id}/allocation/remove', [Admin\NodesController::class, 'allocationRemoveBlock'])->name('admin.nodes.view.allocation.removeBlock');
    Route::post('/view/{node:id}/allocation/alias', [Admin\NodesController::class, 'allocationSetAlias'])->name('admin.nodes.view.allocation.setAlias');
    Route::post('/view/{node:id}/settings/token', Admin\NodeAutoDeployController::class)->name('admin.nodes.view.configuration.token');

    Route::patch('/view/{node:id}/settings', [Admin\NodesController::class, 'updateSettings']);

    Route::delete('/view/{node:id}/delete', [Admin\NodesController::class, 'delete'])->name('admin.nodes.view.delete');
    Route::delete('/view/{node:id}/allocation/remove/{allocation:id}', [Admin\NodesController::class, 'allocationRemoveSingle'])->name('admin.nodes.view.allocation.removeSingle');
    Route::delete('/view/{node:id}/allocations', [Admin\NodesController::class, 'allocationRemoveMultiple'])->name('admin.nodes.view.allocation.removeMultiple');
});

/*
|--------------------------------------------------------------------------
| Mount Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/mounts
|
*/
Route::group(['prefix' => 'mounts', 'middleware' => ['staff:mounts']], function () {
    Route::get('/', [Admin\MountController::class, 'index'])->name('admin.mounts');
    Route::get('/view/{mount:id}', [Admin\MountController::class, 'view'])->name('admin.mounts.view');

    Route::post('/', [Admin\MountController::class, 'create']);
    Route::post('/{mount:id}/eggs', [Admin\MountController::class, 'addEggs'])->name('admin.mounts.eggs');
    Route::post('/{mount:id}/nodes', [Admin\MountController::class, 'addNodes'])->name('admin.mounts.nodes');

    Route::patch('/view/{mount:id}', [Admin\MountController::class, 'update']);

    Route::delete('/{mount:id}/eggs/{egg_id}', [Admin\MountController::class, 'deleteEgg']);
    Route::delete('/{mount:id}/nodes/{node_id}', [Admin\MountController::class, 'deleteNode']);
});

/*
|--------------------------------------------------------------------------
| Nest Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/nests
|
*/
Route::group(['prefix' => 'nests', 'middleware' => ['staff:nests']], function () {
    Route::get('/', [Admin\Nests\NestController::class, 'index'])->name('admin.nests');
    Route::get('/new', [Admin\Nests\NestController::class, 'create'])->name('admin.nests.new');
    Route::get('/view/{nest:id}', [Admin\Nests\NestController::class, 'view'])->name('admin.nests.view');
    Route::get('/egg/new', [Admin\Nests\EggController::class, 'create'])->name('admin.nests.egg.new');
    Route::get('/egg/{egg:id}', [Admin\Nests\EggController::class, 'view'])->name('admin.nests.egg.view');
    Route::get('/egg/{egg:id}/export', [Admin\Nests\EggShareController::class, 'export'])->name('admin.nests.egg.export');
    Route::get('/egg/{egg:id}/variables', [Admin\Nests\EggVariableController::class, 'view'])->name('admin.nests.egg.variables');
    Route::get('/egg/{egg:id}/scripts', [Admin\Nests\EggScriptController::class, 'index'])->name('admin.nests.egg.scripts');

    Route::post('/new', [Admin\Nests\NestController::class, 'store']);
    Route::post('/import', [Admin\Nests\EggShareController::class, 'import'])->name('admin.nests.egg.import');
    Route::post('/egg/new', [Admin\Nests\EggController::class, 'store']);
    Route::post('/egg/{egg:id}/variables', [Admin\Nests\EggVariableController::class, 'store']);

    Route::put('/egg/{egg:id}', [Admin\Nests\EggShareController::class, 'update']);

    Route::patch('/view/{nest:id}', [Admin\Nests\NestController::class, 'update']);
    Route::patch('/egg/{egg:id}', [Admin\Nests\EggController::class, 'update']);
    Route::patch('/egg/{egg:id}/scripts', [Admin\Nests\EggScriptController::class, 'update']);
    Route::patch('/egg/{egg:id}/variables/{variable:id}', [Admin\Nests\EggVariableController::class, 'update'])->name('admin.nests.egg.variables.edit');

    Route::delete('/view/{nest:id}', [Admin\Nests\NestController::class, 'destroy']);
    Route::delete('/egg/{egg:id}', [Admin\Nests\EggController::class, 'destroy']);
    Route::delete('/egg/{egg:id}/variables/{variable:id}', [Admin\Nests\EggVariableController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Ticket Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/tickets
|
*/
Route::group(['prefix' => 'tickets', 'middleware' => ['staff:tickets']], function () {
    Route::get('/', [Admin\TicketController::class, 'index'])->name('admin.tickets');
    Route::get('/view/{ticket:id}', [Admin\TicketController::class, 'view'])->name('admin.tickets.view');

    Route::post('/view/{ticket:id}/reply', [Admin\TicketController::class, 'reply'])->name('admin.tickets.reply');
    Route::patch('/view/{ticket:id}', [Admin\TicketController::class, 'update'])->name('admin.tickets.update');
});

/*
|--------------------------------------------------------------------------
| Coin Voucher & Server Plan Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/vouchers, /admin/plans
|
*/
Route::group(['prefix' => 'vouchers', 'middleware' => ['staff:coins.vouchers']], function () {
    Route::get('/', [Admin\VoucherController::class, 'index'])->name('admin.vouchers');
    Route::post('/', [Admin\VoucherController::class, 'store']);
    Route::get('/{voucher:id}', [Admin\VoucherController::class, 'view'])->name('admin.vouchers.view');
    Route::post('/{voucher:id}/toggle', [Admin\VoucherController::class, 'toggle'])->name('admin.vouchers.toggle');
    Route::delete('/{voucher:id}', [Admin\VoucherController::class, 'delete'])->name('admin.vouchers.delete');
});

Route::group(['prefix' => 'plans', 'middleware' => ['staff:coins.plans']], function () {
    Route::get('/', [Admin\PlanController::class, 'index'])->name('admin.plans');
    Route::get('/{plan:id}', [Admin\PlanController::class, 'edit'])->name('admin.plans.edit');
    Route::post('/', [Admin\PlanController::class, 'store']);
    Route::patch('/{plan:id}', [Admin\PlanController::class, 'update']);
    Route::delete('/{plan:id}', [Admin\PlanController::class, 'delete'])->name('admin.plans.delete');
});

/*
|--------------------------------------------------------------------------
| Coin Settings Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/settings/coins (admins may manage the coin economy)
|
*/
Route::group(['prefix' => 'settings/coins', 'middleware' => ['staff:coins.settings']], function () {
    Route::get('/', [Admin\Settings\CoinsController::class, 'index'])->name('admin.settings.coins');
    Route::patch('/', [Admin\Settings\CoinsController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| Abuse Flag Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/abuse
|
*/
Route::group(['prefix' => 'abuse', 'middleware' => ['staff:servers.moderate']], function () {
    Route::get('/', [Admin\AbuseFlagController::class, 'index'])->name('admin.abuse');
    Route::post('/{flag:id}/resolve', [Admin\AbuseFlagController::class, 'resolve'])->name('admin.abuse.resolve');
});

/*
|--------------------------------------------------------------------------
| Staff Audit Log Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/audit
|
*/
Route::get('/audit', [Admin\AuditLogController::class, 'index'])->name('admin.audit')->middleware('staff:audit');

/*
|--------------------------------------------------------------------------
| Blocked IPs (automatic lockout after failed logins)
|--------------------------------------------------------------------------
|
| Endpoint: /admin/security/ip-blocks
|
*/
Route::group(['prefix' => 'security/ip-blocks', 'middleware' => ['staff:security.ipblock']], function () {
    Route::get('/', [Admin\IpBlockController::class, 'index'])->name('admin.security.ipblocks');
    Route::post('/', [Admin\IpBlockController::class, 'store'])->middleware('throttle:30,1,ipblock-manage');
    Route::delete('/{block:id}', [Admin\IpBlockController::class, 'destroy'])->name('admin.security.ipblocks.delete');
});
