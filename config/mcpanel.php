<?php

return [
    // The GitHub repository (owner/name) and branch this panel updates itself from.
    'repository' => env('MC_PANEL_REPO', 'Knaeckebrot14-12/Recoded-Ptero'),
    'branch' => env('MC_PANEL_BRANCH', 'main'),

    // Optional GitHub token, only needed for private forks or to lift the API rate limit.
    'github_token' => env('MC_PANEL_GITHUB_TOKEN'),

    // When enabled, the panel starts an update on its own as soon as one is published.
    // Editable in Admin -> Settings -> Updates.
    'auto_update' => env('MC_PANEL_AUTO_UPDATE', false),

    // Directory shared with the updater service (see installer/updater). The panel drops
    // update requests in here and reads the progress the updater writes back.
    'updater_dir' => env('MC_UPDATER_DIR', '/app/updater'),

    // Version of the Wings build the installer installs (GitHub release "wings-v<version>" of this repository).
    'wings_version' => env('MC_PANEL_WINGS_VERSION', '1.1.0'),

    // Admin -> Settings -> Login & Registration.
    'registration' => [
        // New sign-ups must confirm their e-mail address before earning coins or getting servers.
        'verify_email' => env('MC_PANEL_VERIFY_EMAIL', true),
        // How many accounts may be registered from one IP address (0 = no limit).
        'max_accounts_per_ip' => (int) env('MC_PANEL_MAX_ACCOUNTS_PER_IP', 2),
    ],

    'discord' => [
        'enabled' => env('MC_PANEL_DISCORD_ENABLED', false),
        'client_id' => env('MC_PANEL_DISCORD_CLIENT_ID'),
        'client_secret' => env('MC_PANEL_DISCORD_CLIENT_SECRET'),
        // Whether someone without an account can create one by logging in with Discord.
        'allow_registration' => env('MC_PANEL_DISCORD_ALLOW_REGISTRATION', true),
    ],

    // Public page at /status showing whether the nodes are online.
    'status_page' => [
        'enabled' => env('MC_PANEL_STATUS_PAGE', true),
    ],

    // Admin -> Settings -> Monitoring. Wings reports CPU, memory and disk every minute; alerts go to a
    // Discord webhook when a node goes offline or crosses a threshold.
    'monitoring' => [
        'discord_webhook' => env('MC_PANEL_MONITORING_WEBHOOK'),
        'notify_offline' => env('MC_PANEL_MONITORING_OFFLINE', true),
        // The same webhook also gets a message for every new support ticket and new registration.
        'notify_tickets' => env('MC_PANEL_MONITORING_TICKETS', true),
        'notify_registrations' => env('MC_PANEL_MONITORING_REGISTRATIONS', true),
        'disk_percent' => (int) env('MC_PANEL_MONITORING_DISK', 90),
        'memory_percent' => (int) env('MC_PANEL_MONITORING_MEMORY', 95),
    ],

    // Admin -> Settings -> Abuse detection. p:abuse:scan runs every 5 minutes and only flags servers
    // for the team (Admin -> Abuse flags); it never suspends anything on its own.
    'abuse' => [
        'enabled' => env('MC_PANEL_ABUSE_ENABLED', true),
        // CPU stays at/above this share of the server's CPU limit for the whole window.
        'cpu_percent' => (int) env('MC_PANEL_ABUSE_CPU_PERCENT', 90),
        'cpu_minutes' => (int) env('MC_PANEL_ABUSE_CPU_MINUTES', 30),
        // Look for miner keywords in the console and the root folder of busy servers.
        'miner_enabled' => env('MC_PANEL_ABUSE_MINER', true),
        // One keyword per line (or comma separated), matched as whole words.
        'miner_keywords' => env('MC_PANEL_ABUSE_KEYWORDS', "xmrig\nminerd\ncpuminer\nstratum+tcp\nstratum+ssl\n--donate-level\nrandomx\ncryptonight\nnicehash\nnbminer\nlolminer\nethminer\nccminer\nxmr-stak"),
        // Outbound traffic far above normal: average MiB per minute over the window.
        'network_enabled' => env('MC_PANEL_ABUSE_NETWORK', true),
        'network_mib_per_min' => (int) env('MC_PANEL_ABUSE_NETWORK_MIB', 50),
        'network_minutes' => (int) env('MC_PANEL_ABUSE_NETWORK_MINUTES', 10),
        'notify_discord' => env('MC_PANEL_ABUSE_NOTIFY_DISCORD', true),
        'notify_push' => env('MC_PANEL_ABUSE_NOTIFY_PUSH', true),
    ],

    // Admin -> Settings -> Design. Logo/favicon files live in /app/var/branding (kept across updates).
    'branding' => [
        'accent' => env('MC_PANEL_ACCENT', ''),
        'background' => env('MC_PANEL_BACKGROUND', ''),
        'logo' => env('MC_PANEL_LOGO', ''),
        'favicon' => env('MC_PANEL_FAVICON', ''),
        'default_theme' => env('MC_PANEL_DEFAULT_THEME', 'dark'),
    ],

    // Admin -> Settings -> IP lockout. IPs with too many failed logins are blocked from the sign-in
    // endpoints for a while (App\Services\Security\IpLockoutService). Private and loopback addresses and
    // the allowlist (one IP or CIDR range per line) are never blocked.
    'ip_lockout' => [
        'enabled' => env('MC_PANEL_IP_LOCKOUT', true),
        'max_attempts' => (int) env('MC_PANEL_IP_LOCKOUT_ATTEMPTS', 10),
        'window_minutes' => (int) env('MC_PANEL_IP_LOCKOUT_WINDOW', 15),
        // First block, second block and third and later blocks within 7 days.
        'block_minutes' => (int) env('MC_PANEL_IP_LOCKOUT_BLOCK', 30),
        'block_minutes_2' => (int) env('MC_PANEL_IP_LOCKOUT_BLOCK_2', 120),
        'block_minutes_3' => (int) env('MC_PANEL_IP_LOCKOUT_BLOCK_3', 1440),
        'allowlist' => env('MC_PANEL_IP_LOCKOUT_ALLOWLIST', ''),
    ],

    // Admin -> Settings -> Subdomains. Users pick name.<domain> for a server; the panel creates the
    // DNS records through the Cloudflare API (token with "Zone.DNS: Edit" for these zones).
    'subdomains' => [
        'enabled' => env('MC_PANEL_SUBDOMAINS', false),
        'cloudflare_token' => env('MC_PANEL_CLOUDFLARE_TOKEN'),
        // Comma separated, e.g. "play.example.com,mc.example.net".
        'domains' => env('MC_PANEL_SUBDOMAIN_DOMAINS', ''),
    ],

    // Browser push for the installable app. The keys are created automatically on first use.
    'push' => [
        'public_key' => env('MC_PANEL_VAPID_PUBLIC'),
        'private_key' => env('MC_PANEL_VAPID_PRIVATE'),
    ],

    // Admin -> Settings -> Roles: JSON {"supporter": [...], "moderator": [...], "admin": [...]}.
    // Empty means the defaults from Pterodactyl\Services\Users\RolePermissions.
    'roles' => [
        'permissions' => env('MC_PANEL_ROLE_PERMISSIONS', ''),
    ],

    // Admin -> Maintenance. "banner" shows the message to everybody, "lock" also keeps
    // everybody except the team out of the panel (game servers keep running).
    'maintenance' => [
        'mode' => env('MC_PANEL_MAINTENANCE_MODE', 'off'),
        'message' => env('MC_PANEL_MAINTENANCE_MESSAGE', ''),
    ],

    // phpMyAdmin at <panel>/phpmyadmin/, opened from Admin -> Databases and the servers' Databases
    // tab. Turned on and off by the owner under Admin -> Settings -> Advanced.
    'phpmyadmin' => [
        'enabled' => env('MC_PANEL_PHPMYADMIN', true),
    ],

    // Sleep mode: Wings stops a server nobody was on for "minutes" and starts it again when a player
    // connects. The default for servers that didn't choose; set under Admin -> Settings -> Advanced.
    'sleep' => [
        'enabled' => env('MC_PANEL_SLEEP', false),
        'minutes' => env('MC_PANEL_SLEEP_MINUTES', 30),
    ],
];
