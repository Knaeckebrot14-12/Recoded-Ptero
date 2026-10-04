# Recoded Ptero

A game server panel for Minecraft hosting, built on the open source [Pterodactyl Panel](https://github.com/pterodactyl/panel) (MIT). On top of stock Pterodactyl it adds:

- **Coin economy**: earn coins via Linkvertise, an AFK page, daily rewards with streaks, vouchers and referrals; spend them in a shop on resources, backups or whole server plans
- **Self-service servers** for normal users, with per-user resource pools and cooldowns
- **Support tickets** with notifications and ratings
- **Roles**: user, supporter, moderator, admin, owner, with an audit log of staff actions. The owner decides under **Settings → Roles** what each role may do (users, servers, nodes, coins, …), including hiding other users' e-mail addresses and IPs and forbidding password changes
- **Minecraft tools** per server: Modrinth plugin installer, player manager (online players, whitelist, operators, bans), server.properties as a form, CPU/RAM/player history graphs, automatic backups with rotation
- **Public registration** with e-mail confirmation and an accounts-per-IP limit against alt accounts, log in with Discord, forgot-password flow, announcements
- **Admin statistics** (users, servers, tickets, coins, node usage), **maintenance mode** (banner or lock-out) and a public **status page** at /status
- **Translations**: English, German, French, Spanish and more, selectable per user
- **Version changer**: switch a Minecraft server between Paper, Purpur, Folia, Fabric, Vanilla and Velocity and any of their versions with one click; the matching Java image is chosen automatically, and a backup can be made first (on by default, never by deleting another backup)
- **phpMyAdmin** at `<panel>/phpmyadmin/`: admins open it with a database host's account, users open their own server databases with one click, without any extra login
- **Subdomains**: users give their servers an address like `name.play.example.com` (A + SRV records through the Cloudflare API, no port needed to join)
- **Node monitoring**: CPU, memory and disk graphs per node, Discord alerts when a node goes offline or runs full, and one-click Wings updates for all nodes
- **Design**: logo, icon, accent colour and background under Settings → Design; every user can switch between the dark and a light theme
- **Installable app (PWA)** with push notifications for server crashes, ticket answers, coin reminders and node alerts
- **Security**: security headers, TLS 1.2+ only, breached-password check, e-mail on logins from new addresses, confirmation link before a password reset, 2FA reminder for admins, verified Wings downloads
- **One-click and automatic updates** from within the panel

## Install

On a fresh Debian, Ubuntu, Rocky, Alma or CentOS server, as root:

```bash
bash <(curl -sSL https://raw.githubusercontent.com/Knaeckebrot14-12/Recoded-Ptero/main/install.sh)
```

The installer sets up Docker, downloads this repository to `/opt/recoded-ptero`, asks a few questions (how the panel is reached, owner account) and starts everything. It can also install [Wings](https://github.com/pterodactyl/wings), the daemon that runs the game servers, on the same or another machine.

What the installer asks:

1. **What to do**: install Recoded Ptero, upgrade an existing Pterodactyl panel, install Wings, both, update, uninstall, go back to the normal Pterodactyl panel, or set up and check the databases and phpMyAdmin. Nothing is installed before you pick an option.
2. **How the panel is reached**:
   - `1` HTTP by IP or domain (quick test setups),
   - `2` HTTPS with Let's Encrypt: enter the domain (its DNS A record must already point at the server) and an e-mail for certificate notices,
   - `3` behind your own reverse proxy (Nginx, Caddy, Cloudflare Tunnel): enter the public URL and a local port.
3. **Timezone**: the server's timezone is suggested; press Enter or type another one (e.g. Europe/Berlin).
4. **Owner account**: e-mail, username, name and password (leave empty to generate one).
5. **Automatic updates**: on or off (can be changed later under Settings → Updates).
6. **Wings on this server too?** Say yes and panel, node and Wings are set up in one run.

Then it builds and starts everything (this can take a few minutes the first time) and prints the URL and login.

The system's own packages are brought up to date too: `apt update && apt upgrade -y` (dnf/yum on RHEL-like systems) runs before the installation changes anything and once more at the end. Your changed config files are kept. If an update needs a restart (a new kernel), the installer says so; everything starts again on its own after `reboot`. Set `MC_SKIP_SYSTEM_UPGRADE=1` to skip this.

Every hour the server's RAM cache (page cache) is emptied by a systemd timer (`sync`, then `echo 1 > /proc/sys/vm/drop_caches`). Pending writes are saved first and running programs keep their memory; only cached file contents are read from disk again. Installations that were set up earlier get the timer through the normal update (the updater sets it up once, via [`installer/host-extras.sh`](installer/host-extras.sh)); an existing timer is never touched. Turn it off with `systemctl disable --now recoded-ptero-dropcache.timer` (stays off), or install without it using `MC_DROP_CACHES=0`.

**Wings and certificates** are handled automatically too:

- *Panel and Wings on the same machine*: the installer creates the node in the panel, writes the Wings configuration and starts Wings. If the panel uses HTTPS, it also gets a Let's Encrypt certificate for Wings (the panel's domain can be reused, no extra DNS record needed).
- *Wings on another machine*: enter the panel URL; for an HTTPS panel also the machine's domain, and the certificate is created. The installer then tells you exactly what to enter when creating the node and asks for the token from the node's Configuration tab.
- *Wings version*: nodes get the Wings build from this repository's release [wings-v1.0.0](https://github.com/Knaeckebrot14-12/Recoded-Ptero/releases/tag/wings-v1.0.0) — the official Pterodactyl Wings (v1.13.3, MIT), unchanged apart from reporting version 1.0.0. Running the Wings option again on a node switches it to this build.
- *Databases for game servers*: the installer can also set up a MariaDB server next to Wings (container recoded-gamedb, port 3306, credentials in /etc/recoded-ptero/gamedb.env) and adds it under Admin → Databases, so users can create databases for plugins like LuckPerms right away. On a separate Wings machine it prints the values to enter there.
- Running the Wings option again on the panel server repairs the setup (same node, fresh configuration). Wings gets a Docker network range that does not collide with the panel's own Docker network, and the panel talks to Wings directly on the machine instead of through the public address.
- Firewall: if ufw is active, the installer opens only the ports the panel and Wings need (80/443 or your panel port, 8080 for Wings, 2022 for SFTP). Game server ports are never created or opened automatically; add them per node under Admin → Nodes → Allocation.
- Before the first certificate, the installer shows Let's Encrypt's current Terms of Service and asks you to agree (unattended installs: MC_LE_AGREE=1).
- All certificates renew automatically (the panel's inside its container, Wings' via the certbot timer, restarting Wings afterwards).

## Log in with Discord

Admin → Discord login (Settings → Login & Registration) shows the redirect URL and a four-step guide: create an application in the [Discord Developer Portal](https://discord.com/developers/applications), add the redirect URL under OAuth2, paste Client ID and Client Secret, tick the box. New Discord users can get an account automatically (can be turned off); existing users link Discord on their account page, or are linked automatically through the same verified e-mail address. Accounts with two-factor authentication keep using password + code.

## Upgrade from Pterodactyl

Run the same command on the server of your existing Pterodactyl panel (1.x, served by nginx, as in the official docs) and choose **Upgrade**. All users, servers, nodes, eggs, API keys and settings move over; logins, the panel URL and your Wings nodes keep working.

How data loss is prevented:

- Recoded Ptero is built first while your panel keeps running; the downtime is only the move itself (a few minutes, game servers keep running).
- Before anything changes: a dump of the database, copies of `.env`, the nginx site and the crontab, and an archive of the panel files are saved to `/opt/recoded-ptero/backups/pterodactyl-<date>/`.
- The old database is only read, never changed; the old panel directory is kept as it is.
- After the copy, every table's row count is compared with the original. The `APP_KEY` is carried over, so encrypted data (node tokens, 2FA, database host passwords) stays readable.
- If any step fails, the old panel is switched back on automatically. Later you can still go back with `rollback.sh` in the backup folder.

Afterwards the upgraded panel is set up like a new installation, so no feature is missing:

- **Wings on the same server** is replaced by Recoded Ptero's build (graphs, crash reports, one-click updates) with its configuration kept; game servers keep running. The panel reaches it directly, and the node gets all of the machine's memory and disk (limits are only raised, never lowered).
- **Database hosts at `127.0.0.1` or `localhost`** (MySQL on the same server) keep working from the Docker container: a small `hostdb` service forwards them through a socket, so MySQL, its users and the addresses users see stay unchanged and MySQL isn't opened to any network. Every database host is then tested; the installer says which one needs a change.
- If there is no database host yet, it offers the same database server for game servers as a new installation.
- Automatic updates, the hourly RAM cache cleanup and automatic security updates are set up as on a new installation.
- **Nodes on other servers** are listed at the end: run the installer there, choose the Wings option and keep the existing connection. That only swaps in the new Wings.

Requirements: 2 CPU cores and 4 GB RAM are recommended (the first build needs the memory; the installer offers to add swap on smaller servers). Ports 80 and 443 for the panel.

### Going back to the normal Pterodactyl panel

Run the installer on the same server and choose **Go back to the normal Pterodactyl panel** (it needs the upgrade backup from above). It asks which state to restore:

- **Carry over what happened since the upgrade** (default): the data of Recoded Ptero (new users, servers, tickets, settings) is copied into the old panel's database; MariaDB collations are converted to the ones MySQL knows. Only offered when the old panel's code knows all database changes Recoded Ptero has made to Pterodactyl's own tables.
- **Exactly the state of the upgrade**: the old database stays as it was; what was done in Recoded Ptero since then stays only in the backup.

Before anything is switched, both databases and the nginx site are saved to `/opt/recoded-ptero/backups/before-revert-<date>/`. Then the old nginx site, cron entry and queue worker come back and the old panel starts again. If its migrations fail or it does not answer, everything is undone and Recoded Ptero keeps running. Recoded Ptero is only stopped, never deleted. Unattended: `MC_NONINTERACTIVE=1 MC_ACTION=revert MC_REVERT_MODE=carry|exact MC_REVERT_CONFIRM=yes`.

### Databases and phpMyAdmin in one step

**Set up and check databases and phpMyAdmin** can be run at any time and makes sure everything for databases works:

1. Is phpMyAdmin part of the installed panel? If not (an older image), the panel is updated to the current version, which includes it.
2. Is there a database host? If not and Wings runs on this machine, a database server for game servers is created and registered in the panel (it asks first).
   - If port 3306 is already taken by a MySQL/MariaDB on this machine (for example the one of your old Pterodactyl panel), that server is used: the installer logs in as its administrator (root through the socket, or an account you type in), creates a dedicated account `recoded_panel` and registers it. Existing databases and accounts are not touched. A server that only listens on `127.0.0.1` is reached through the forwarding from step 3. Unattended: `MC_HSQL_USER` and `MC_HSQL_PASS`.
3. Database hosts at `127.0.0.1` or `localhost` get their forwarding into the container.
4. phpMyAdmin is switched on and tested for real: web server routes, the connection of the panel and of phpMyAdmin to every database host, and a complete sign-in through the ticket flow. Every host is reported as working, or with the reason it does not.

The same check can be run by hand with `docker compose exec panel php artisan p:phpmyadmin:check` in `/opt/recoded-ptero` (`--enable` switches phpMyAdmin on, `--no-login` skips the sign-in test). Install, upgrade and the Wings option run it automatically at the end.

Unattended installs work with environment variables, for example:

```bash
MC_NONINTERACTIVE=1 MC_ACTION=panel MC_MODE=1 MC_HOST=203.0.113.10 MC_ADMIN_EMAIL=me@example.com \
  bash <(curl -sSL https://raw.githubusercontent.com/Knaeckebrot14-12/Recoded-Ptero/main/install.sh)
```

## Login lockout

Too many failed logins (10 within 15 minutes by default; passwords, 2FA codes and passkeys count) block the person who made them from the sign-in pages (login, 2FA, registration, password reset) for 30 minutes, then 2 hours, then 24 hours. Admin → Settings → IP lockout changes the limits; Admin → Blocked IPs lists and lifts blocks. People who are already logged in are never affected.

Only the culprit is blocked, never a shared address:

- **The visitor's own address**, and **their browser**: sign-in pages set a random security cookie (`mcpanel_device`, nothing but a random number), so a browser stays recognisable when its IP address changes. The cookie is stored only as a hash.
- **Cloudflare**: addresses of Cloudflare servers are never blocked. Behind Cloudflare's proxy the visitor's own address is read from the `CF-Connecting-IP` header (and only when the request really comes from a Cloudflare address, so the header can't be faked); no extra setup is needed. Without that header only the browser is blocked.
- **Other reverse proxies**: set `TRUSTED_PROXIES` in `/opt/recoded-ptero/.env` to your proxy's address so visitors are told apart. Private addresses are never blocked; the browser cookie still works.
- IPv6 clients are blocked as their whole /64, since they can switch addresses inside it freely.
- The allowlist (IPs and ranges, same settings page) is never blocked. If the team locks itself out: `recoded-ptero artisan p:security:unblock-ip <IP or d:id from the Blocked IPs page>`.

## Updates

The panel checks GitHub for new versions every five minutes. As owner, open **Admin → Settings → Updates**:

- **Update now** downloads and builds the new version next to the running one, backs up the database, swaps the container and rolls back automatically if the new version does not start.
- **Install updates automatically** does the same as soon as a new version is pushed to this repository.

Behind the scenes a small `updater` container (started by the installer, no ports exposed) watches a shared folder for update requests from the panel. If the panel is ever unreachable, update from the server with:

```bash
recoded-ptero update
```

Other helpers: `recoded-ptero status | logs | backup | restart | artisan <command>`. Backups of the last 7 updates are kept in `/opt/recoded-ptero/backups`.

## Wings updates and monitoring

Nodes run this repository's Wings build: the official [pterodactyl/wings](https://github.com/pterodactyl/wings) (MIT) plus a small patch ([`installer/wings/recoded-ptero.patch`](installer/wings/recoded-ptero.patch)) that adds a usage endpoint for the graphs, crash reports for push notifications and a self-update. The self-update only installs `wings_linux_<arch>` from this repository's `wings-v<version>` releases and only when it matches the release's `checksums.txt`.

Under **Admin → Nodes** each node shows its usage; when a newer Wings release exists, **Update Wings** (per node) or **Update all** installs it. Game servers keep running while Wings restarts. Nodes that still run an older Wings get the patched one once by running the installer's Wings option on them.

Alerts are configured under **Admin → Settings → Monitoring** (Discord webhook, disk and memory thresholds). The same Discord channel can also get a message for every new support ticket and new registration.

## phpMyAdmin

[phpMyAdmin](https://www.phpmyadmin.net) (GPL-2.0) is part of the panel image and runs at `https://<your panel>/phpmyadmin/` (no extra container, port or certificate). The image downloads the official release at build time and checks it against the SHA-256 pinned in the [`Dockerfile`](Dockerfile); the panel's configuration for it is in [`.github/docker/phpmyadmin`](.github/docker/phpmyadmin).

- **Admins** (and the owner) see the address and an **Open phpMyAdmin** button per host under **Admin → Databases**. It signs in with the host's own account, so databases, tables and users can be created and dropped and SQL can be run. Supporters, moderators and admins without the *Databases* permission (Settings → Roles) don't see it. Every opening is written to the audit log.
- **Users** get a button per database in their server's **Databases** tab (and the address in the connection details). It signs in as that database's own user, so only that database is reachable. It needs the permission to see the database password.
- There is **no login form**: every sign-in starts in the panel, which checks the permissions and hands the browser a one-time ticket (60 seconds, usable once, only in that browser session). Requests without it go back to the panel. A phpMyAdmin session ends after 30 minutes without activity.
- The owner turns it on or off under **Admin → Settings → Advanced**; turning it off also signs everybody out of phpMyAdmin.

## Publishing updates (for maintainers)

Every push to the `main` branch is an update. Bump the number in [`VERSION`](VERSION) for a readable version label, commit and push; installed panels show the new commit and its commit messages under **Settings → Updates**.

A new Wings build: `installer/wings/build.sh <version>` builds both architectures from the patch, then upload `installer/wings/dist/*` as release `wings-v<version>` and set `wings_version` in [`config/mcpanel.php`](config/mcpanel.php).

To make a fork update from its own repository, set `MC_PANEL_REPO=<owner>/<repo>` in `/opt/recoded-ptero/.env` (the installer honours `MC_PANEL_REPO` and `MC_PANEL_BRANCH` too).

## Development

```bash
docker compose up -d --build panel   # with a local docker-compose.yml (not tracked)
```

The production stack is [`docker-compose.prod.yml`](docker-compose.prod.yml); the installer and updater live in [`install.sh`](install.sh) and [`installer/`](installer).

## License

MIT, see [LICENSE.md](LICENSE.md). Pterodactyl® is a registered trademark of its respective owners; this project is not affiliated with them.
