<?php

namespace Pterodactyl\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Services\StaffAudit;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Security\IpLockoutService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class IpLockoutSettingsController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private IpLockoutService $lockout,
        private SettingsRepositoryInterface $settings,
    ) {
    }

    public function index(Request $request): View
    {
        $client = $this->lockout->describeClient($request);

        return view('admin.settings.iplockout', [
            'currentIp' => $client['ip'],
            // real | cloudflare | shared | private: what can be blocked for this visitor, see IpLockoutService::describeClient().
            'currentMode' => $client['mode'],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'max_attempts' => 'required|integer|min:3|max:1000',
            'window_minutes' => 'required|integer|min:1|max:1440',
            'block_minutes' => 'required|integer|min:1|max:43200',
            'block_minutes_2' => 'required|integer|min:1|max:43200',
            'block_minutes_3' => 'required|integer|min:1|max:43200',
            'allowlist' => ['bail', 'nullable', 'string', 'max:5000', function ($attribute, $value, $fail) {
                foreach (preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $entry) {
                    if (!IpLockoutService::isValidEntry($entry)) {
                        $fail(trans('admin/ipblock.settings.allowlist_invalid', ['entry' => mb_substr($entry, 0, 60)]));

                        return;
                    }
                }
            }],
        ]);

        $allowlist = implode("\n", IpLockoutService::parseAllowlist((string) ($data['allowlist'] ?? '')));

        $this->settings->set('settings::mcpanel:ip_lockout:enabled', $request->boolean('enabled') ? 'true' : 'false');
        foreach (['max_attempts', 'window_minutes', 'block_minutes', 'block_minutes_2', 'block_minutes_3'] as $key) {
            $this->settings->set('settings::mcpanel:ip_lockout:' . $key, (string) $data[$key]);
        }
        $this->settings->set('settings::mcpanel:ip_lockout:allowlist', $allowlist === '' ? '(empty)' : $allowlist);

        StaffAudit::record('settings.ipblock');
        $this->alert->success(trans('admin/ipblock.settings.saved'))->flash();

        return redirect()->route('admin.settings.iplockout');
    }
}
