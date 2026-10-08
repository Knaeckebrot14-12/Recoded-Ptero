#!/usr/bin/env bash
# Recoded Ptero installer.
#
#   bash <(curl -sSL https://raw.githubusercontent.com/Knaeckebrot14-12/Recoded-Ptero/main/install.sh)
#
# Installs the panel (Docker based, with one-click and automatic updates) and/or Wings, the
# daemon that runs the game servers. Run it as root on a fresh Debian/Ubuntu/RHEL-family server.
#
# Unattended use: set MC_NONINTERACTIVE=1 plus the MC_* variables used by the questions below,
# for example MC_ACTION=panel MC_MODE=1 MC_HOST=1.2.3.4 MC_ADMIN_EMAIL=me@example.com.

set -uo pipefail

GITHUB_REPO="${MC_PANEL_REPO:-Knaeckebrot14-12/Recoded-Ptero}"
GITHUB_BRANCH="${MC_PANEL_BRANCH:-main}"
INSTALL_DIR="${INSTALL_DIR:-/opt/recoded-ptero}"
# Wings build installed on nodes: official Pterodactyl Wings, published as release "wings-v<version>" here.
WINGS_VERSION="${MC_WINGS_VERSION:-1.2.0}"
COMPOSE_FILE="$INSTALL_DIR/docker-compose.prod.yml"

if [ -t 1 ]; then
    C_RESET=$'\033[0m'; C_BOLD=$'\033[1m'; C_RED=$'\033[31m'; C_GREEN=$'\033[32m'; C_YELLOW=$'\033[33m'; C_BLUE=$'\033[36m'; C_DIM=$'\033[2m'
else
    C_RESET=""; C_BOLD=""; C_RED=""; C_GREEN=""; C_YELLOW=""; C_BLUE=""; C_DIM=""
fi

# Everything the installer runs is written here; the screen only shows one line per step.
INSTALL_LOG="${INSTALL_LOG:-/var/log/recoded-ptero-install.log}"

info() { printf '%s==>%s %s\n' "$C_BLUE" "$C_RESET" "$*"; }
ok() { printf '%s ✔%s %s\n' "$C_GREEN" "$C_RESET" "$*"; }
warn() { printf '%s !%s %s\n' "$C_YELLOW" "$C_RESET" "$*"; }
die() { printf '%s ✘ %s%s\n' "$C_RED" "$*" "$C_RESET" >&2; exit 1; }

# run_step "Building the panel" "Panel built" command [args...]
# Shows "==> Building the panel (0:42)" with a running timer while the command works, then
# " ✔ Panel built". The command's own output goes to $INSTALL_LOG and is only shown when it fails.
run_step() {
    local doing="$1" done_msg="$2" pid rc start elapsed
    shift 2
    printf '\n===== %s  (%s)\n' "$doing" "$(date '+%F %T')" >> "$INSTALL_LOG"
    start=$(date +%s)
    "$@" >> "$INSTALL_LOG" 2>&1 < /dev/null &
    pid=$!
    if [ -t 1 ]; then
        while kill -0 "$pid" 2>/dev/null; do
            elapsed=$(( $(date +%s) - start ))
            printf '\r%s==>%s %s %s(%d:%02d)%s' "$C_BLUE" "$C_RESET" "$doing" "$C_DIM" $((elapsed / 60)) $((elapsed % 60)) "$C_RESET"
            sleep 1
        done
        printf '\r\033[K'
    else
        printf '%s==>%s %s\n' "$C_BLUE" "$C_RESET" "$doing"
    fi
    wait "$pid"
    rc=$?
    if [ "$rc" -eq 0 ]; then
        printf '%s ✔%s %s\n' "$C_GREEN" "$C_RESET" "$done_msg"
    else
        printf '%s ✘ %s failed%s\n' "$C_RED" "$doing" "$C_RESET"
        tail -n 20 "$INSTALL_LOG" | sed 's/^/     /'
        printf '     Full log: %s\n' "$INSTALL_LOG"
    fi
    return "$rc"
}

# ---------------------------------------------------------------- prompts

# ask VAR "Question" [default]  — the answer ends up in $VAR. MC_<VAR> in the environment answers it silently.
ask() {
    local var="$1" question="$2" default="${3:-}" preset="MC_$1" answer=""
    if [ -n "${!preset:-}" ]; then printf -v "$var" '%s' "${!preset}"; return; fi
    if [ "${MC_NONINTERACTIVE:-0}" = "1" ]; then printf -v "$var" '%s' "$default"; return; fi
    if [ -n "$default" ]; then
        read -r -p "$question [$default]: " answer </dev/tty
    else
        read -r -p "$question: " answer </dev/tty
    fi
    printf -v "$var" '%s' "${answer:-$default}"
}

# ask_secret VAR "Question"  — like ask, but without echo and without a default.
ask_secret() {
    local var="$1" question="$2" preset="MC_$1" answer=""
    if [ -n "${!preset:-}" ]; then printf -v "$var" '%s' "${!preset}"; return; fi
    if [ "${MC_NONINTERACTIVE:-0}" = "1" ]; then printf -v "$var" '%s' ""; return; fi
    read -r -s -p "$question: " answer </dev/tty
    echo
    printf -v "$var" '%s' "$answer"
}

# True when a question really waits for somebody to type (not answered by MC_<VAR> or a non-interactive run).
can_retry() {
    local preset="MC_$1"
    [ -z "${!preset:-}" ] && [ "${MC_NONINTERACTIVE:-0}" != "1" ]
}

# Asks for the owner's e-mail until it looks valid (an unattended install has no one to ask and stops).
ask_admin_email() {
    while true; do
        ask ADMIN_EMAIL "E-mail" ""
        [[ "$ADMIN_EMAIL" == *@*.* ]] && return 0
        can_retry ADMIN_EMAIL || die "Please enter a valid e-mail address."
        warn "That does not look like an e-mail address. Please try again."
    done
}

# Asks for the owner's password until it is long enough and was typed the same twice; empty
# generates one. An unattended install (MC_ADMIN_PASS, MC_NONINTERACTIVE) can't be asked again and stops.
ask_admin_password() {
    local repeat=""
    GENERATED_PASS=0
    while true; do
        ask_secret ADMIN_PASS "Password (leave empty to generate one)"
        if [ -z "$ADMIN_PASS" ]; then
            ADMIN_PASS="$(random_string 20)"; GENERATED_PASS=1
            return 0
        fi
        if [ "${#ADMIN_PASS}" -lt 8 ]; then
            can_retry ADMIN_PASS || die "The password needs at least 8 characters."
            warn "The password needs at least 8 characters. Please try again."
            continue
        fi
        can_retry ADMIN_PASS || return 0
        ask_secret ADMIN_PASS_REPEAT "Repeat the password"
        repeat="$ADMIN_PASS_REPEAT"
        [ "$repeat" = "$ADMIN_PASS" ] && return 0
        warn "The two passwords are not the same. Please try again."
    done
}

# confirm "Question" y|n  — returns success for yes. Non-interactive runs take the default.
confirm() {
    local question="$1" default="${2:-n}" answer="" hint="y/N"
    [ "$default" = "y" ] && hint="Y/n"
    if [ "${MC_NONINTERACTIVE:-0}" = "1" ]; then [ "$default" = "y" ]; return; fi
    read -r -p "$question [$hint]: " answer </dev/tty
    answer="${answer:-$default}"
    [[ "$answer" =~ ^[Yy] ]]
}

random_string() {
    local length="${1:-32}"
    openssl rand -base64 96 | tr -dc 'A-Za-z0-9' | head -c "$length"
}

# ---------------------------------------------------------------- system checks

require_root() {
    [ "$(id -u)" -eq 0 ] || die "Please run this installer as root (for example: sudo bash <(curl -sSL ...))."
}

detect_system() {
    [ "$(uname -s)" = "Linux" ] || die "This installer only supports Linux."
    case "$(uname -m)" in
        x86_64|amd64) ARCH="amd64" ;;
        aarch64|arm64) ARCH="arm64" ;;
        *) die "Unsupported CPU architecture: $(uname -m)" ;;
    esac

    if command -v apt-get >/dev/null 2>&1; then PKG="apt"
    elif command -v dnf >/dev/null 2>&1; then PKG="dnf"
    elif command -v yum >/dev/null 2>&1; then PKG="yum"
    else die "Unsupported distribution: no apt, dnf or yum found. Debian, Ubuntu, Rocky, Alma and CentOS are supported."
    fi
    command -v systemctl >/dev/null 2>&1 || warn "systemd was not found, some steps (starting Docker/Wings on boot) may not work."
}

# Brings the system's own packages up to date (apt update + apt upgrade). Runs before the installation
# changes anything and once more at the end, so the server finishes with everything current.
# MC_SKIP_SYSTEM_UPGRADE=1 skips it.
upgrade_system() {
    local doing="$1" done_msg="$2"
    [ "${MC_SKIP_SYSTEM_UPGRADE:-0}" = "1" ] && return 0
    case "$PKG" in
        # Existing config files are kept, needrestart restarts services without asking, and a
        # package manager that is still busy (unattended-upgrades on a fresh server) is waited for.
        apt) run_step "$doing" "$done_msg" env DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a sh -c \
                'apt-get -o DPkg::Lock::Timeout=300 update -y && apt-get -o DPkg::Lock::Timeout=300 -o Dpkg::Options::=--force-confdef -o Dpkg::Options::=--force-confold upgrade -y' ;;
        dnf) run_step "$doing" "$done_msg" dnf upgrade -y ;;
        yum) run_step "$doing" "$done_msg" yum update -y ;;
    esac || warn "Not all system updates could be installed; the installation goes on. Details: $INSTALL_LOG"
    SYSTEM_UPGRADED=1
}

# Tells whether the system updates need a restart to take effect (a new kernel, for example).
reboot_notice() {
    if [ -f /var/run/reboot-required ]; then
        warn "The system updates need a restart to take full effect. Everything starts again on its own:  reboot"
    fi
}

# Hourly timer that empties the RAM cache (page cache). "sync" writes pending data to disk first, so
# nothing is lost; programs keep their memory, only cached file contents are read from disk again.
# MC_DROP_CACHES=0 skips it; turn it off later with: systemctl disable --now recoded-ptero-dropcache.timer
setup_cache_drop() {
    if [ "${MC_DROP_CACHES:-1}" != "1" ]; then
        # Remember the "no", so the updater (installer/host-extras.sh) doesn't set it up later.
        if [ -d "$INSTALL_DIR" ]; then mkdir -p "$INSTALL_DIR/state" && touch "$INSTALL_DIR/state/no-dropcache"; fi
        return 0
    fi
    [ -w /proc/sys/vm/drop_caches ] || return 0
    if command -v systemctl >/dev/null 2>&1 && [ -d /etc/systemd/system ]; then
        cat > /etc/systemd/system/recoded-ptero-dropcache.service <<'EOF'
[Unit]
Description=Recoded Ptero: empty the RAM cache

[Service]
Type=oneshot
ExecStart=/bin/sh -c 'sync && echo 1 > /proc/sys/vm/drop_caches'
EOF
        cat > /etc/systemd/system/recoded-ptero-dropcache.timer <<'EOF'
[Unit]
Description=Recoded Ptero: empty the RAM cache every hour

[Timer]
OnCalendar=hourly
RandomizedDelaySec=120

[Install]
WantedBy=timers.target
EOF
        systemctl daemon-reload >/dev/null 2>&1
        systemctl enable --now recoded-ptero-dropcache.timer >/dev/null 2>&1 || return 0
    else
        echo "0 * * * * root sync && echo 1 > /proc/sys/vm/drop_caches" > /etc/cron.d/recoded-ptero-dropcache
    fi
    ok "RAM cache is emptied every hour"
}

# Security updates of the operating system install themselves every day (Debian/Ubuntu:
# unattended-upgrades, which by default takes only the security repository). MC_AUTO_SECURITY_UPDATES=0 skips it.
setup_security_updates() {
    [ "${MC_AUTO_SECURITY_UPDATES:-1}" = "1" ] || return 0
    [ "$PKG" = "apt" ] || return 0
    run_step "Turning on automatic security updates" "Security updates install themselves daily" \
        env DEBIAN_FRONTEND=noninteractive apt-get -o DPkg::Lock::Timeout=300 install -y unattended-upgrades || return 0
    cat > /etc/apt/apt.conf.d/20auto-upgrades <<'EOF'
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Unattended-Upgrade "1";
EOF
}

remove_cache_drop() {
    systemctl disable --now recoded-ptero-dropcache.timer >/dev/null 2>&1 || true
    rm -f /etc/systemd/system/recoded-ptero-dropcache.service /etc/systemd/system/recoded-ptero-dropcache.timer \
        /etc/cron.d/recoded-ptero-dropcache
    systemctl daemon-reload >/dev/null 2>&1 || true
}

install_packages() {
    [ "${SYSTEM_UPGRADED:-0}" = "1" ] || upgrade_system "Updating the system (apt update && apt upgrade)" "System updated"
    case "$PKG" in
        apt) run_step "Installing required packages" "Required packages installed" \
                env DEBIAN_FRONTEND=noninteractive sh -c 'apt-get update -y && apt-get install -y curl git ca-certificates openssl tar' ;;
        dnf) run_step "Installing required packages" "Required packages installed" dnf install -y curl git ca-certificates openssl tar ;;
        yum) run_step "Installing required packages" "Required packages installed" yum install -y curl git ca-certificates openssl tar ;;
    esac || die "Could not install packages."
}

install_docker() {
    if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
        ok "Docker $(docker --version | awk '{print $3}' | tr -d ,) is already installed"
    else
        run_step "Installing Docker" "Docker installed" sh -c 'curl -fsSL https://get.docker.com | sh' \
            || die "Docker installation failed. Install Docker manually and run this installer again."
        docker compose version >/dev/null 2>&1 || die "Docker was installed but the 'docker compose' plugin is missing."
    fi
    if command -v systemctl >/dev/null 2>&1; then
        systemctl enable --now docker >/dev/null 2>&1 || true
    fi
    docker info >/dev/null 2>&1 || die "The Docker daemon is not running."
}

# Building the panel needs a few GB of memory; offer swap on small servers.
ensure_swap() {
    [ -f /.dockerenv ] && return 0
    local mem_kb swap_kb
    mem_kb="$(awk '/MemTotal/ {print $2}' /proc/meminfo)"
    swap_kb="$(awk '/SwapTotal/ {print $2}' /proc/meminfo)"
    if [ "$mem_kb" -lt 3500000 ] && [ "$swap_kb" -lt 2000000 ]; then
        warn "This server has less than 4 GB of memory. Building the panel needs a lot, so a swap file is recommended."
        if confirm "Create a 4 GB swap file at /swapfile?" y; then
            if [ ! -f /swapfile ]; then
                (fallocate -l 4G /swapfile 2>/dev/null || dd if=/dev/zero of=/swapfile bs=1M count=4096 status=none) \
                    && chmod 600 /swapfile && mkswap /swapfile >/dev/null && swapon /swapfile \
                    && { grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab; } \
                    && ok "Swap enabled" || warn "Could not create the swap file, continuing without it."
            fi
        fi
    fi
}

public_ip() {
    curl -4 -fsS --max-time 6 https://api.ipify.org 2>/dev/null \
        || curl -4 -fsS --max-time 6 https://ifconfig.me 2>/dev/null \
        || hostname -I 2>/dev/null | awk '{print $1}'
}

port_in_use() {
    command -v ss >/dev/null 2>&1 || return 1
    ss -ltnH "sport = :$1" 2>/dev/null | grep -q .
}

system_timezone() {
    local tz
    tz="$(timedatectl show -p Timezone --value 2>/dev/null || cat /etc/timezone 2>/dev/null)"
    [ -z "$tz" ] && [ -L /etc/localtime ] && tz="$(readlink /etc/localtime | sed 's|.*/zoneinfo/||')"
    echo "${tz:-UTC}"
}

# A timezone like Europe/Berlin. Checked against the system's timezone database when it exists.
valid_timezone() {
    [[ "$1" =~ ^[A-Za-z0-9_+-]+(/[A-Za-z0-9_+-]+)*$ ]] || return 1
    [ -d /usr/share/zoneinfo ] || return 0
    [ -f "/usr/share/zoneinfo/$1" ]
}

ask_timezone() {
    local default
    default="$(system_timezone)"
    valid_timezone "$default" || default="UTC"
    while true; do
        ask TIMEZONE "Select timezone (e.g. Europe/Berlin, America/New_York)" "$default"
        valid_timezone "$TIMEZONE" && return 0
        # A preset or non-interactive value that isn't valid can't be asked again.
        if [ -n "${MC_TIMEZONE:-}" ] || [ "${MC_NONINTERACTIVE:-0}" = "1" ]; then
            die "Unknown timezone: $TIMEZONE"
        fi
        warn "Unknown timezone '$TIMEZONE'. Examples: Europe/Berlin, Europe/Vienna, Europe/Zurich, UTC."
    done
}

# Opens the ports the panel and Wings need (web, daemon, SFTP) when ufw is active.
# Game server ports are never opened automatically.
open_firewall_ports() {
    if command -v ufw >/dev/null 2>&1 && ufw status 2>/dev/null | grep -q "Status: active"; then
        local port
        for port in "$@"; do ufw allow "$port" >/dev/null 2>&1; done
        ok "Opened ports in ufw: $*"
    fi
}

dc() {
    docker compose -f "$COMPOSE_FILE" --project-directory "$INSTALL_DIR" "$@"
}

# ---------------------------------------------------------------- panel

# Waits until the panel answers on the given port (the first start migrates the database).
wait_for_panel() {
    local port="$1" waited=0 code
    while [ "$waited" -lt 420 ]; do
        code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 "http://${MC_WAIT_HOST:-127.0.0.1}:$port/auth/login" 2>/dev/null || true)"
        if [ -n "$code" ] && [ "$code" != "000" ] && [ "$code" -lt 500 ]; then return 0; fi
        sleep 5
        waited=$((waited + 5))
    done
    return 1
}

create_owner() {
    local tries=0
    until dc exec -T panel php artisan p:user:make --email="$ADMIN_EMAIL" --username="$ADMIN_USER" \
        --name-first="$ADMIN_FIRST" --name-last="$ADMIN_LAST" --password="$ADMIN_PASS" --admin=1 --role=owner; do
        tries=$((tries + 1))
        [ "$tries" -ge 5 ] && return 1
        sleep 5
    done
}

install_panel() {
    if [ -f "$INSTALL_DIR/.env" ]; then
        die "A panel is already installed in $INSTALL_DIR. Use the update option instead (or run: recoded-ptero update)."
    fi
    if [ -e "$INSTALL_DIR" ] && [ -n "$(ls -A "$INSTALL_DIR" 2>/dev/null)" ]; then
        die "$INSTALL_DIR already exists and is not empty. Remove it or set INSTALL_DIR to another location."
    fi

    if [ -f /var/www/pterodactyl/artisan ]; then
        warn "A Pterodactyl panel was found in /var/www/pterodactyl."
        warn "To keep its users, servers and nodes, choose 'Upgrade' instead of a new installation."
        confirm "Install a separate, empty Recoded Ptero anyway?" n || die "Aborted. Run the installer again and choose the upgrade option."
    fi

    install_packages
    install_docker
    ensure_swap

    echo
    printf '%s%s%s\n' "$C_BOLD" "How should the panel be reachable?" "$C_RESET"
    echo "  [1] HTTP only, by IP address or domain (no encryption, quickest)"
    echo "  [2] HTTPS with a free Let's Encrypt certificate (needs a domain pointing at this server)"
    echo "  [3] I already run a reverse proxy (Nginx, Caddy, Cloudflare Tunnel...) that handles HTTPS"
    ask MODE "Choose 1, 2 or 3" "1"

    local server_ip domain port
    server_ip="$(public_ip)"
    HTTP_BIND="0.0.0.0"; HTTP_PORT="${MC_HTTP_PORT:-80}"; HTTPS_PORT="${MC_HTTPS_PORT:-443}"; LE_EMAIL=""; TRUSTED_PROXIES=""

    case "$MODE" in
        1)
            ask HOST "Server IP address or domain" "$server_ip"
            [ -n "$HOST" ] || die "A host is required."
            if port_in_use "$HTTP_PORT"; then
                warn "Port 80 is already in use on this server."
                ask HTTP_PORT "Port for the panel" "8085"
            fi
            APP_URL="http://$HOST"
            [ "$HTTP_PORT" != "80" ] && APP_URL="$APP_URL:$HTTP_PORT"
            ;;
        2)
            ask DOMAIN "Domain of the panel (for example panel.example.com)" ""
            [ -n "$DOMAIN" ] || die "A domain is required for HTTPS."
            ask LE_EMAIL "E-mail address for Let's Encrypt (expiry notices)" ""
            [ -n "$LE_EMAIL" ] || die "An e-mail address is required for Let's Encrypt."
            agree_letsencrypt_tos
            port_in_use 80 && die "Port 80 is in use. Let's Encrypt needs ports 80 and 443; stop the service using them (for example Apache or Nginx) first."
            port_in_use 443 && die "Port 443 is in use. Let's Encrypt needs ports 80 and 443; stop the service using them first."
            domain_ip="$(getent hosts "$DOMAIN" | awk '{print $1; exit}')"
            if [ -z "$domain_ip" ] || { [ -n "$server_ip" ] && [ "$domain_ip" != "$server_ip" ]; }; then
                warn "$DOMAIN resolves to '${domain_ip:-nothing}', but this server's address is '$server_ip'."
                warn "Let's Encrypt will fail until the DNS record points here."
                confirm "Continue anyway?" n || die "Aborted. Fix the DNS record and run the installer again."
            fi
            APP_URL="https://$DOMAIN"
            ;;
        3)
            ask APP_URL "Public URL of the panel (for example https://panel.example.com)" ""
            [[ "$APP_URL" =~ ^https?:// ]] || die "Please enter a full URL starting with http:// or https://"
            ask HTTP_PORT "Local port your reverse proxy should forward to" "8085"
            HTTP_BIND="127.0.0.1"; HTTPS_PORT=8443; TRUSTED_PROXIES="*"
            ;;
        *) die "Please choose 1, 2 or 3." ;;
    esac
    APP_URL="${APP_URL%/}"

    echo
    ask_timezone

    echo
    printf '%s%s%s\n' "$C_BOLD" "Owner account (full access)" "$C_RESET"
    ask_admin_email
    ask ADMIN_USER "Username" "admin"
    ask ADMIN_FIRST "First name" "Admin"
    ask ADMIN_LAST "Last name" "User"
    ask_admin_password

    echo
    AUTO_UPDATE=0
    confirm "Update the panel automatically whenever a new version is published?" n && AUTO_UPDATE=1

    # Asked up front so the whole installation runs through in one go afterwards.
    if [ -z "${WITH_WINGS:-}" ]; then
        WITH_WINGS=0
        if [ -n "${MC_WITH_WINGS:-}" ]; then
            [ "$MC_WITH_WINGS" = "1" ] && WITH_WINGS=1
        elif [ "${MC_NONINTERACTIVE:-0}" != "1" ]; then
            confirm "Also install Wings (the node that runs the game servers) on this server?" y && WITH_WINGS=1
        fi
    fi

    run_step "Downloading Recoded Ptero" "Recoded Ptero downloaded" \
        git clone --quiet --branch "$GITHUB_BRANCH" "https://github.com/$GITHUB_REPO.git" "$INSTALL_DIR" \
        || die "Could not download the repository. Check the internet connection and that github.com/$GITHUB_REPO exists."
    local commit
    commit="$(git -C "$INSTALL_DIR" rev-parse HEAD)"
    git -C "$INSTALL_DIR" config core.fileMode false

    (
        umask 077
        cat > "$INSTALL_DIR/.env" <<EOF
# Written by install.sh. Contains secrets, keep it private. Changes apply after: recoded-ptero restart
INSTALL_DIR=$INSTALL_DIR
COMPOSE_PROJECT_NAME=recodedptero
MC_PANEL_REPO=$GITHUB_REPO
MC_PANEL_BRANCH=$GITHUB_BRANCH
APP_URL=$APP_URL
APP_TIMEZONE=$TIMEZONE
APP_SERVICE_AUTHOR=$ADMIN_EMAIL
DB_PASSWORD=$(random_string 32)
DB_ROOT_PASSWORD=$(random_string 32)
HTTP_BIND=$HTTP_BIND
HTTP_PORT=$HTTP_PORT
HTTPS_PORT=$HTTPS_PORT
LE_EMAIL=$LE_EMAIL
TRUSTED_PROXIES=$TRUSTED_PROXIES
EOF
    )
    mkdir -p "$INSTALL_DIR/state" "$INSTALL_DIR/backups"
    ln -sf "$INSTALL_DIR/installer/recoded-ptero" /usr/local/bin/recoded-ptero
    chmod +x "$INSTALL_DIR/installer/recoded-ptero" "$INSTALL_DIR/installer/updater/updater.sh"
    ok "Configuration written"

    run_step "Building the updater" "Updater built" dc build updater || die "Could not build the updater service."
    run_step "Building the panel (this can take a few minutes the first time)" "Panel built" \
        dc build --build-arg "MC_COMMIT=$commit" panel \
        || die "The build failed (a common reason is too little memory: 4 GB or swap is recommended)."
    run_step "Starting the services" "Services started" dc up -d || die "Could not start the services."
    run_step "Setting up the database and starting the panel" "Panel is running" wait_for_panel "$HTTP_PORT" \
        || die "The panel did not come up in time. Check: recoded-ptero logs panel"
    run_step "Creating the owner account" "Owner account created" create_owner \
        || die "Could not create the owner account. Try: recoded-ptero artisan p:user:make --role=owner"

    if [ "$MODE" = "2" ]; then
        run_step "Testing the automatic renewal of the panel certificate" "Panel certificate renews automatically (checked twice a day, renewed 30 days before it expires)" \
            dc exec -T panel php artisan p:ssl:renew --dry-run \
            || warn "The renewal test failed. HTTPS works for now, but check port 80 and the DNS record before the certificate expires."
        dc exec -T panel php artisan p:ssl:renew >/dev/null 2>&1 || true
    fi

    [ "$AUTO_UPDATE" = "1" ] && dc exec -T panel php artisan p:update:auto on >/dev/null 2>&1 && ok "Automatic updates enabled"

    case "$MODE" in
        1) open_firewall_ports "$HTTP_PORT/tcp" ;;
        2) open_firewall_ports 80/tcp 443/tcp ;;
    esac

    echo
    printf '%s%s%s\n' "$C_GREEN" "======================================================" "$C_RESET"
    printf '%s%s%s\n' "$C_GREEN$C_BOLD" " Recoded Ptero is installed" "$C_RESET"
    printf '%s%s%s\n' "$C_GREEN" "======================================================" "$C_RESET"
    echo " URL:       $APP_URL"
    echo " E-mail:    $ADMIN_EMAIL"
    echo " Username:  $ADMIN_USER"
    if [ "$GENERATED_PASS" = "1" ]; then
        echo " Password:  $ADMIN_PASS   (generated, change it after logging in)"
    else
        echo " Password:  the one you entered"
    fi
    echo
    echo " Next steps:"
    echo "  1. Log in and set up mail under Admin > Settings > Mail."
    echo "  2. Updates: Admin > Settings > Updates (button + automatic updates)."
    if [ "${WITH_WINGS:-0}" = "1" ]; then
        echo "  3. Wings is installed next and connected to the panel automatically."
    else
        echo "  3. Game servers need Wings: run this installer again and choose the Wings option."
        echo "     On this machine it creates the node and certificate by itself; on another"
        echo "     machine it guides you through connecting it."
    fi
    echo
    echo " Handy commands:  recoded-ptero status | update | logs | backup | restart"
    echo " Files:           $INSTALL_DIR  (secrets in $INSTALL_DIR/.env)"
    [ "$MODE" = "3" ] && echo " Reverse proxy:   forward $APP_URL to http://127.0.0.1:$HTTP_PORT"
    echo
}

update_panel() {
    [ -f "$INSTALL_DIR/.env" ] || die "No panel installation found in $INSTALL_DIR."
    info "Updating the panel..."
    git -C "$INSTALL_DIR" config core.fileMode false
    exec bash "$INSTALL_DIR/installer/updater/updater.sh" run "installer-$(date +%s)" false 0
}

uninstall_panel() {
    [ -f "$INSTALL_DIR/.env" ] || die "No panel installation found in $INSTALL_DIR."
    warn "This stops the panel and removes the containers."
    if confirm "Also DELETE ALL DATA (database, settings, uploads) and $INSTALL_DIR?" n; then
        local answer=""
        if [ "${MC_NONINTERACTIVE:-0}" = "1" ]; then answer="DELETE"; else read -r -p "Type DELETE to confirm: " answer </dev/tty; fi
        [ "$answer" = "DELETE" ] || die "Aborted, nothing was removed."
        dc down -v --remove-orphans
        docker image rm recodedptero-panel:latest recodedptero-panel:rollback recodedptero-updater:latest >/dev/null 2>&1 || true
        rm -f /usr/local/bin/recoded-ptero
        rm -rf "$INSTALL_DIR"
        [ -f /usr/local/bin/wings ] || remove_cache_drop
        ok "Panel and all its data were removed."
    else
        dc down --remove-orphans
        ok "Panel stopped. Data is kept; start it again with: docker compose -f $COMPOSE_FILE up -d"
    fi
}

# ---------------------------------------------------------------- certificates

is_ip() { [[ "$1" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; }

# Let's Encrypt requires agreeing to its Subscriber Agreement before issuing certificates. Asked once
# per run; MC_LE_AGREE=1 agrees in unattended installs.
LE_AGREED=0
agree_letsencrypt_tos() {
    [ "$LE_AGREED" = "1" ] && return 0
    local tos
    tos="$(curl -fsS --max-time 8 https://acme-v02.api.letsencrypt.org/directory 2>/dev/null | grep -o '"termsOfService": *"[^"]*"' | sed 's/.*"\(http[^"]*\)"/\1/')"
    echo
    echo "Let's Encrypt needs you to agree to its Terms of Service (Subscriber Agreement):"
    echo "  ${tos:-https://letsencrypt.org/repository/}"
    if [ "${MC_LE_AGREE:-}" = "1" ]; then
        LE_AGREED=1
        return 0
    fi
    confirm "Do you agree?" n || die "Without agreeing no certificate can be requested. Run the installer again and choose HTTP or your own reverse proxy instead."
    LE_AGREED=1
}

# Warns when DOMAIN doesn't point at this server, since Let's Encrypt would then fail.
check_dns() {
    local domain="$1" server_ip domain_ip
    server_ip="$(public_ip)"
    domain_ip="$(getent hosts "$domain" | awk '{print $1; exit}')"
    if [ -z "$domain_ip" ] || { [ -n "$server_ip" ] && [ "$domain_ip" != "$server_ip" ]; }; then
        warn "$domain resolves to '${domain_ip:-nothing}', but this server's address is '$server_ip'."
        warn "Create a DNS A record: $domain -> $server_ip (and wait a few minutes)."
        confirm "Try to get the certificate anyway?" n || die "Aborted. Fix the DNS record and run the installer again."
    fi
}

install_certbot() {
    command -v certbot >/dev/null 2>&1 && return 0
    case "$PKG" in
        apt) run_step "Installing certbot" "Certbot installed" env DEBIAN_FRONTEND=noninteractive apt-get install -y certbot ;;
        dnf) run_step "Installing certbot" "Certbot installed" sh -c 'dnf install -y epel-release; dnf install -y certbot' ;;
        yum) run_step "Installing certbot" "Certbot installed" sh -c 'yum install -y epel-release; yum install -y certbot' ;;
    esac
    command -v certbot >/dev/null 2>&1 || die "Could not install certbot."
}

# Let's Encrypt briefly needs port 80. Sets CERT_PRE/CERT_POST to pause whatever uses it;
# certbot stores these hooks, so automatic renewals do the same.
port80_hooks() {
    CERT_PRE=""; CERT_POST=""
    port_in_use 80 || return 0
    local panel_container
    panel_container="$(env_value COMPOSE_PROJECT_NAME)"
    panel_container="${panel_container:-recodedptero}-panel-1"
    if docker ps --format '{{.Names}}' 2>/dev/null | grep -qx "$panel_container"; then
        CERT_PRE="docker stop $panel_container >/dev/null"; CERT_POST="docker start $panel_container >/dev/null"
        return 0
    fi
    local svc
    for svc in nginx apache2 httpd caddy haproxy lighttpd; do
        if systemctl is-active --quiet "$svc" 2>/dev/null; then
            CERT_PRE="systemctl stop $svc >/dev/null"; CERT_POST="systemctl start $svc >/dev/null"
            return 0
        fi
    done
    die "Port 80 is used by another program. Let's Encrypt needs it for a moment; stop that program and run the installer again."
}

enable_cert_renewal() {
    if systemctl list-unit-files 2>/dev/null | grep -q '^certbot.timer'; then
        systemctl enable --now certbot.timer >/dev/null 2>&1
    elif systemctl list-unit-files 2>/dev/null | grep -q '^certbot-renew.timer'; then
        systemctl enable --now certbot-renew.timer >/dev/null 2>&1
    else
        echo "17 3,15 * * * root certbot renew -q" > /etc/cron.d/recoded-ptero-certbot
    fi
}

# obtain_certificate DOMAIN EMAIL — certificate in /etc/letsencrypt/live/DOMAIN (where Wings
# looks for it), renewed automatically; Wings restarts after each renewal to load it.
obtain_certificate() {
    local domain="$1" email="$2"
    install_certbot

    mkdir -p /etc/letsencrypt/renewal-hooks/deploy
    cat > /etc/letsencrypt/renewal-hooks/deploy/recoded-ptero-wings.sh <<'EOF'
#!/bin/sh
# Installed by the Recoded Ptero installer: Wings only reads its certificate on start.
systemctl is-active --quiet wings && systemctl restart wings
exit 0
EOF
    chmod +x /etc/letsencrypt/renewal-hooks/deploy/recoded-ptero-wings.sh

    if [ -f "/etc/letsencrypt/live/$domain/fullchain.pem" ]; then
        ok "A certificate for $domain already exists"
    else
        agree_letsencrypt_tos
        check_dns "$domain"
        port80_hooks
        open_firewall_ports 80/tcp
        local args=(certonly --standalone --non-interactive --agree-tos -m "$email" -d "$domain")
        [ -n "$CERT_PRE" ] && args+=(--pre-hook "$CERT_PRE" --post-hook "$CERT_POST")
        run_step "Requesting a Let's Encrypt certificate for $domain" "Certificate for $domain installed" certbot "${args[@]}" \
            || die "Could not get a certificate for $domain. Check that the domain points at this server and port 80 is reachable from the internet."
    fi
    enable_cert_renewal
    # A dry run against Let's Encrypt's staging server proves the automatic renewal will work later.
    # It is only a test, so it must never hold the installation up: after 4 minutes it is given up on.
    run_step "Testing the automatic renewal of the certificate" "Certificate renews automatically (checked twice a day, renewed 30 days before it expires)" \
        timeout 240 certbot renew --dry-run --cert-name "$domain" \
        || warn "The renewal test failed or took too long. The certificate works for now, but check port 80 and the DNS record before it expires."
    # The test stops whatever uses port 80 and starts it again afterwards; if it was cut off, make sure that happened.
    [ -n "${CERT_POST:-}" ] && sh -c "$CERT_POST" >/dev/null 2>&1
    return 0
}

# ---------------------------------------------------------------- database server for game servers
#
# Plugins like LuckPerms need a MySQL database. Pterodactyl can create one per server, but only
# once a "database host" exists. This sets up a MariaDB container next to Wings and registers it.

GAMEDB_CONTAINER="recoded-gamedb"
GAMEDB_ENV="/etc/recoded-ptero/gamedb.env"

gamedb_value() {
    grep -m1 "^$1=" "$GAMEDB_ENV" 2>/dev/null | cut -d= -f2-
}

# The port game servers and the panel reach the database server on (3306 unless that is taken).
gamedb_port() {
    local p
    p="$(gamedb_value DB_PORT)"
    echo "${p:-3306}"
}

# The first port from 3306 on that nothing listens on.
free_db_port() {
    local p
    for p in 3306 $(seq 3307 3330); do
        port_in_use "$p" || { echo "$p"; return 0; }
    done
    return 1
}

# Does the database server on port 3306 accept connections from outside this machine?
db_listens_on_network() {
    ss -ltnH 'sport = :3306' 2>/dev/null | awk '{print $4}' | grep -qvE '^(127\.|\[::1\])'
}

wait_for_gamedb() {
    local tries=0
    until docker exec "$GAMEDB_CONTAINER" healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; do
        tries=$((tries + 1))
        [ "$tries" -ge 40 ] && return 1
        sleep 3
    done
}

grant_gamedb_user() {
    # The panel creates a database and a user per server, so its account needs GRANT OPTION.
    docker exec "$GAMEDB_CONTAINER" sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" -e "GRANT ALL PRIVILEGES ON *.* TO \"$MARIADB_USER\"@\"%\" WITH GRANT OPTION; FLUSH PRIVILEGES;"'
}

prepare_gamedb() {
    wait_for_gamedb && grant_gamedb_user
}

start_gamedb() {
    docker run -d --name "$GAMEDB_CONTAINER" --restart unless-stopped \
        -p "$(gamedb_port):3306" -v recoded_gamedb:/var/lib/mysql --env-file "$GAMEDB_ENV" \
        mariadb:11 --bind-address=0.0.0.0
}

# Creates the container once; later runs keep it (and its data) and only make sure it runs.
setup_game_database() {
    if docker ps -a --format '{{.Names}}' | grep -qx "$GAMEDB_CONTAINER"; then
        docker start "$GAMEDB_CONTAINER" >/dev/null 2>&1
        run_step "Starting the database server for game servers" "Database server for game servers is running" wait_for_gamedb \
            || { warn "The database server for game servers does not start. See: docker logs $GAMEDB_CONTAINER"; return 1; }
        return 0
    fi

    local db_port=3306
    if port_in_use 3306; then
        # A MySQL/MariaDB already runs here (e.g. the one of the old Pterodactyl panel). One that is
        # reachable from the network is used as it is. One that only listens on 127.0.0.1 can't be
        # reached by game servers (they would see 127.0.0.1 as themselves), so it is left alone and
        # a new database server gets the next free port, reachable at the server's own address.
        if db_listens_on_network; then
            adopt_local_mysql
            return $?
        fi
        db_port="$(free_db_port)" || { warn "No free port for a database server (3306-3330 are all in use)."; return 1; }
        info "The database server already on this machine only listens on 127.0.0.1, so game servers couldn't reach it."
        info "Setting up a separate one for game servers on port $db_port (the existing one stays untouched)."
    fi

    mkdir -p "$(dirname "$GAMEDB_ENV")"
    (
        umask 077
        cat > "$GAMEDB_ENV" <<EOF
# Database server for game servers (container $GAMEDB_CONTAINER). Keep this file private.
DB_PORT=$db_port
MARIADB_ROOT_PASSWORD=$(random_string 32)
MARIADB_USER=pterodactyluser
MARIADB_PASSWORD=$(random_string 32)
EOF
    )

    run_step "Downloading the database server for game servers" "Database server downloaded" docker pull mariadb:11 \
        || { warn "Could not download the database server."; return 1; }
    start_gamedb >/dev/null 2>&1 || { warn "Could not start the database server for game servers."; return 1; }
    run_step "Setting up the database server for game servers" "Database server for game servers is running" prepare_gamedb \
        || { warn "The database server did not become ready. See: docker logs $GAMEDB_CONTAINER"; return 1; }
    open_firewall_ports "$db_port/tcp"
}

# The address of the game database server: the node's address, or 127.0.0.1 for a MySQL that only
# listens on this machine (adopt_local_mysql writes DB_HOST then).
gamedb_host() {
    local host
    host="$(gamedb_value DB_HOST)"
    [ -n "$host" ] || host="${NODE_FQDN:-}"
    # Never a loopback address: users and their game servers must be able to reach it.
    case "$host" in ""|localhost|127.*) host="$(public_ip)" ;; esac
    echo "$host"
}

# Panel on this machine: add the database server in the panel directly.
register_game_database_local() {
    local id host
    host="$(gamedb_host)"
    id="$(dc exec -T panel php artisan p:database-host:quick-setup --host="$host" --port="$(gamedb_port)" \
        --username="$(gamedb_value MARIADB_USER)" --password="$(gamedb_value MARIADB_PASSWORD)" \
        --node="${NODE_ID:-}" 2>&1 | tr -d '\r' | tail -n1)"
    if [[ "$id" =~ ^[0-9]+$ ]]; then
        ok "Database host $host:$(gamedb_port) added to the panel: users can now create databases for their servers"
    else
        warn "The database server runs, but the panel could not add it: $id"
        print_game_database_details
    fi
}

# Runs the SQL on stdin as an administrator of the MySQL/MariaDB on this machine (see find_host_sql_admin).
host_sql() {
    case "$HSQL_MODE" in
        socket) "$HSQL_BIN" -N -B -uroot ;;
        debian) "$HSQL_BIN" --defaults-file=/etc/mysql/debian.cnf -N -B ;;
        login) MYSQL_PWD="$HSQL_PASS" "$HSQL_BIN" -N -B -h127.0.0.1 -P3306 -u"$HSQL_USER" ;;
        docker) docker run --rm -i --network host -e MYSQL_PWD="$HSQL_PASS" mariadb:11 mariadb -N -B -h127.0.0.1 -P3306 -u"$HSQL_USER" ;;
        *) return 1 ;;
    esac 2>>"$INSTALL_LOG"
}

# Finds a way to administer the MySQL on port 3306: root through its socket (the Debian/Ubuntu
# default), the maintenance account, or an account the user types in. Sets HSQL_MODE and friends.
find_host_sql_admin() {
    HSQL_BIN=""; HSQL_MODE=""; HSQL_USER=""; HSQL_PASS=""
    command -v mariadb >/dev/null 2>&1 && HSQL_BIN=mariadb
    [ -z "$HSQL_BIN" ] && command -v mysql >/dev/null 2>&1 && HSQL_BIN=mysql

    if [ -n "$HSQL_BIN" ]; then
        HSQL_MODE=socket
        echo "SELECT 1" | host_sql >/dev/null 2>&1 && return 0
        if [ -r /etc/mysql/debian.cnf ]; then
            HSQL_MODE=debian
            echo "SELECT 1" | host_sql >/dev/null 2>&1 && return 0
        fi
    fi

    HSQL_MODE=login; [ -n "$HSQL_BIN" ] || HSQL_MODE=docker
    local tries=0
    while [ "$tries" -lt 3 ]; do
        if [ "$tries" = "0" ]; then
            echo "  The installer could not log in to that database server by itself."
            echo "  Enter an administrator account of it (one that may create users, e.g. root); it is only used now and not stored."
        fi
        ask HSQL_USER "  MySQL admin user" "root"
        ask_secret HSQL_PASS "  Password"
        [ -n "$HSQL_PASS" ] || return 1
        echo "SELECT 1" | host_sql >/dev/null 2>&1 && return 0
        can_retry HSQL_USER || return 1
        warn "That login was not accepted."
        tries=$((tries + 1))
    done
    return 1
}

# Port 3306 is taken by a MySQL/MariaDB that already runs on this machine (e.g. the database of the
# old Pterodactyl panel). Instead of giving up, a dedicated account for the panel is created in it
# (all rights with GRANT OPTION, as Pterodactyl's database hosts need), and the panel registers it
# like the container variant. The existing databases and accounts are not touched.
adopt_local_mysql() {
    local user pass sql h
    info "Port 3306 is used by a database server that already runs on this machine; using it for game servers."
    if ! find_host_sql_admin; then
        warn "No administrator access to that database server, so nothing was set up for game servers."
        echo "     Create an account that may create databases and users in it, then add it yourself under"
        echo "     Admin > Databases > Create New (or run this option again). Unattended: set MC_HSQL_USER and MC_HSQL_PASS."
        return 1
    fi

    mkdir -p "$(dirname "$GAMEDB_ENV")"
    user="$(gamedb_value MARIADB_USER)"; pass="$(gamedb_value MARIADB_PASSWORD)"
    if [ "$(gamedb_value ADOPTED)" != "1" ] || [ -z "$user" ] || [ -z "$pass" ]; then
        user="recoded_panel"; pass="$(random_string 32)"
        (
            umask 077
            cat > "$GAMEDB_ENV" <<EOF
# Account of Recoded Ptero in the database server that already ran on this machine. Keep this file private.
ADOPTED=1
MARIADB_USER=$user
MARIADB_PASSWORD=$pass
DB_PORT=3306
EOF
        )
    fi
    # The panel arrives from 127.0.0.1 (forwarding) or from Docker's 172.x addresses.
    sql=""
    for h in localhost 127.0.0.1 '172.%'; do
        sql="$sql CREATE USER IF NOT EXISTS '$user'@'$h' IDENTIFIED BY '$pass';"
        sql="$sql ALTER USER '$user'@'$h' IDENTIFIED BY '$pass';"
        sql="$sql GRANT ALL PRIVILEGES ON *.* TO '$user'@'$h' WITH GRANT OPTION;"
    done
    sql="$sql FLUSH PRIVILEGES;"
    if ! printf '%s\n' "$sql" | host_sql >/dev/null; then
        warn "Could not create the account for the panel in the database server. See: $INSTALL_LOG"
        return 1
    fi
    # MySQL 8's default login method needs a secure connection the panel's PHP can't use over TCP;
    # the classic one works everywhere (a server that doesn't offer it simply keeps the default).
    if ! echo "SELECT VERSION()" | host_sql 2>/dev/null | grep -qi mariadb; then
        sql=""
        for h in localhost 127.0.0.1 '172.%'; do
            sql="$sql ALTER USER '$user'@'$h' IDENTIFIED WITH mysql_native_password BY '$pass';"
        done
        printf '%s\n' "$sql" | host_sql >/dev/null 2>&1 || true
    fi
    ok "Account '$user' for the panel created in the existing database server (its other databases and accounts are untouched)"
    echo "     Open port 3306 in your firewall only if game servers on other machines need it."
    return 0
}

print_game_database_details() {
    echo
    printf '%s%s%s\n' "$C_BOLD" "Add the database server in the panel" "$C_RESET"
    echo "  Admin > Databases > Create New, and enter:"
    echo "    Host:         $(gamedb_host)"
    echo "    Port:         $(gamedb_port)"
    echo "    Username:     $(gamedb_value MARIADB_USER)"
    echo "    Password:     $(gamedb_value MARIADB_PASSWORD)"
    echo "    Linked Node:  this node"
    echo "  (The password is also stored in $GAMEDB_ENV.)"
}

# ---------------------------------------------------------------- wings

install_wings_binary() {
    mkdir -p /etc/pterodactyl
    local base="https://github.com/$GITHUB_REPO/releases/download/wings-v$WINGS_VERSION" tmp="/usr/local/bin/.wings.new" want have
    run_step "Downloading Wings v$WINGS_VERSION" "Wings v$WINGS_VERSION downloaded" \
        sh -c "curl -fsSL -o '$tmp' '$base/wings_linux_$ARCH' && curl -fsSL -o /tmp/recoded-wings-checksums.txt '$base/checksums.txt'" \
        || die "Could not download Wings."
    # Only a binary that matches the release's published checksum gets installed.
    want="$(awk -v f="wings_linux_$ARCH" '$2 == f || $2 == "*" f { print $1 }' /tmp/recoded-wings-checksums.txt)"
    have="$(sha256sum "$tmp" | awk '{print $1}')"
    rm -f /tmp/recoded-wings-checksums.txt
    if [ -z "$want" ] || [ "$want" != "$have" ]; then
        rm -f "$tmp"
        die "The downloaded Wings does not match its checksum; nothing was installed."
    fi
    chmod 755 "$tmp"
    # Replacing by rename works while an older Wings is still running.
    mv -f "$tmp" /usr/local/bin/wings

    cat > /etc/systemd/system/wings.service <<'EOF'
[Unit]
Description=Pterodactyl Wings Daemon
After=docker.service
Requires=docker.service
PartOf=docker.service

[Service]
User=root
WorkingDirectory=/etc/pterodactyl
LimitNOFILE=4096
PIDFile=/var/run/wings/daemon.pid
ExecStart=/usr/local/bin/wings
Restart=on-failure
StartLimitInterval=180
StartLimitBurst=30
RestartSec=5s

[Install]
WantedBy=multi-user.target
EOF
    if command -v systemctl >/dev/null 2>&1; then
        systemctl daemon-reload
        systemctl enable wings >/dev/null 2>&1
    fi
}

env_value() {
    grep -m1 "^$1=" "$INSTALL_DIR/.env" 2>/dev/null | cut -d= -f2-
}

start_wings() {
    if ! command -v systemctl >/dev/null 2>&1; then
        warn "systemd is not available here; start Wings yourself with: /usr/local/bin/wings"
        return 0
    fi
    systemctl restart wings >/dev/null 2>&1
    sleep 5
    if systemctl is-active --quiet wings; then
        ok "Wings is running"
    else
        warn "Wings did not start. See: journalctl -u wings -n 50"
    fi
}

# Wings creates a Docker network for the game servers, by default 172.18.0.0/16. When the panel
# runs in Docker on the same machine, Docker usually gave that very range to the panel's network,
# and Wings then fails to start ("pool overlaps"). Pick a range nobody uses and tell Wings about it.
configure_wings_network() {
    local config=/etc/pterodactyl/config.yml used candidate existing
    [ -f "$config" ] || return 0
    grep -q '^docker:' "$config" && return 0

    # Wings' own network from an earlier run: keep it, and describe it correctly in the config.
    existing="$(docker network inspect pterodactyl_nw -f '{{range .IPAM.Config}}{{.Subnet}} {{end}}' 2>/dev/null | awk '{print $1}')"
    if [ -n "$existing" ]; then
        [ "$existing" = "172.18.0.0/16" ] && return 0
        write_wings_network "${existing%.0.0/16}"
        return 0
    fi

    used="$(docker network inspect $(docker network ls -q) -f '{{range .IPAM.Config}}{{.Subnet}} {{end}}' 2>/dev/null) $(ip -4 route 2>/dev/null | awk '{print $1}')"
    grep -q '172\.18\.' <<<"$used" || return 0

    for candidate in 172.29 172.30 172.31 172.28 172.27 10.250 10.251 10.252; do
        if ! grep -q "${candidate//./\\.}\." <<<"$used"; then
            write_wings_network "$candidate"
            ok "Wings uses the network $candidate.0.0/16 (172.18.0.0/16 is taken by the panel's Docker network)"
            return 0
        fi
    done
    warn "Could not find a free network range for Wings; it may fail to start (journalctl -u wings)."
}

write_wings_network() {
    cat >> /etc/pterodactyl/config.yml <<EOF
docker:
  network:
    interface: $1.0.1
    interfaces:
      v4:
        subnet: $1.0.0/16
        gateway: $1.0.1
EOF
}

# Waits until the panel can talk to Wings and explains what to check when it can't.
check_node_connection() {
    local url="$1" code="" tries=0
    info "Checking that the panel can reach Wings..."
    while [ "$tries" -lt 20 ]; do
        # 401 = Wings answered and asked for its token, which is exactly what we want to see here.
        code="$(dc exec -T panel curl -s -o /dev/null -w '%{http_code}' --max-time 5 "$url/api/system" 2>/dev/null || true)"
        if [ "$code" = "401" ] || [ "$code" = "200" ]; then
            ok "The panel reaches Wings at $url"
            return 0
        fi
        sleep 3
        tries=$((tries + 1))
    done

    warn "The panel cannot reach Wings at $url (last answer: ${code:-none})."
    if command -v systemctl >/dev/null 2>&1 && ! systemctl is-active --quiet wings; then
        warn "Wings is not running. Its last log lines:"
        journalctl -u wings -n 15 --no-pager 2>/dev/null | sed 's/^/     /'
    else
        warn "Wings runs, so something is in between. Check:"
        echo "     - Is the domain behind Cloudflare's proxy (orange cloud)? Set it to 'DNS only'."
        echo "     - Does a firewall block port 8080? (ufw allow 8080/tcp)"
    fi
    echo "     Fix it and run this installer again with the Wings option; it repairs the setup."
    return 1
}

set_env_value() {
    local key="$1" value="$2" file="$INSTALL_DIR/.env"
    if grep -q "^$key=" "$file"; then
        sed -i "s|^$key=.*|$key=$value|" "$file"
    else
        echo "$key=$value" >> "$file"
    fi
}

# The node gets everything the machine has: all memory and the full size of the disk game servers
# live on. Sets mem_mb and disk_mb (MiB).
machine_limits() {
    mem_mb=$(( $(awk '/MemTotal/ {print $2}' /proc/meminfo) / 1024 ))
    [ "$mem_mb" -lt 1024 ] && mem_mb=1024
    disk_mb=$( (df -Pm /var/lib/pterodactyl 2>/dev/null || df -Pm /) | awk 'NR==2 {print $2}')
    [ "${disk_mb:-0}" -lt 5120 ] && disk_mb=5120
}

# The panel runs on this machine: create the node through the panel and connect Wings, no copying needed.
# Running it again repairs an existing setup (same node, fresh config).
setup_local_node() {
    local app_url panel_host email scheme node_id mem_mb disk_mb tries=0
    app_url="$(env_value APP_URL)"
    panel_host="${app_url#*://}"; panel_host="${panel_host%%/*}"; panel_host="${panel_host%%:*}"
    email="$(env_value LE_EMAIL)"; [ -n "$email" ] || email="$(env_value APP_SERVICE_AUTHOR)"

    # An older panel may lack what this needs (the direct panel -> Wings route); bring it up to date first.
    run_step "Making sure the panel is up to date" "Panel is up to date" \
        bash "$INSTALL_DIR/installer/updater/updater.sh" run "wings-setup-$(date +%s)" false 0 \
        || warn "The panel could not be updated right now; continuing with the installed version."

    # Installing panel and Wings together: no extra questions, Wings uses the panel's address.
    [ "${WITH_WINGS:-0}" = "1" ] && [ -z "${NODE_FQDN:-}" ] && NODE_FQDN="$panel_host"

    if [[ "$app_url" == https://* ]]; then
        # Browsers only allow the server console over HTTPS when Wings has a certificate too.
        scheme="https"
        ask NODE_FQDN "Domain for Wings (the panel's domain works fine)" "$panel_host"
        is_ip "$NODE_FQDN" && die "With HTTPS, Wings needs a domain name instead of an IP address."
        obtain_certificate "$NODE_FQDN" "$email"
    else
        scheme="http"
        ask NODE_FQDN "Address players and the panel use to reach this machine" "$panel_host"
    fi

    # Let the panel container reach this machine's Wings directly under the node's name.
    if ! is_ip "$NODE_FQDN" && [ "$NODE_FQDN" != "localhost" ]; then
        set_env_value LOCAL_NODE_FQDN "$NODE_FQDN"
        dc up -d --no-deps panel >/dev/null 2>&1 || warn "Could not restart the panel container."
        local waited=0
        until dc exec -T panel php -v >/dev/null 2>&1 || [ "$waited" -ge 90 ]; do sleep 3; waited=$((waited + 3)); done
    fi

    machine_limits

    info "Creating the node in the panel..."
    until node_id="$(dc exec -T panel php artisan p:node:quick-setup --fqdn="$NODE_FQDN" --scheme="$scheme" \
            --memory="$mem_mb" --disk="$disk_mb" 2>/dev/null | tr -d '\r' | tail -n1)" \
            && [[ "$node_id" =~ ^[0-9]+$ ]]; do
        tries=$((tries + 1))
        [ "$tries" -ge 12 ] && die "Could not create the node in the panel. Check: recoded-ptero logs panel"
        sleep 5
    done
    NODE_ID="$node_id"
    ok "Node #$node_id is set up in the panel"

    dc exec -T panel php artisan p:node:configuration "$node_id" --format=yaml > /etc/pterodactyl/config.yml.new \
        && [ -s /etc/pterodactyl/config.yml.new ] || die "Could not read the node configuration from the panel."
    mv -f /etc/pterodactyl/config.yml.new /etc/pterodactyl/config.yml
    chmod 600 /etc/pterodactyl/config.yml
    configure_wings_network
    ok "Wings is configured"
    start_wings
    check_node_connection "$scheme://$NODE_FQDN:8080" || true
}

# The panel runs somewhere else: prepare the certificate, then connect with the token from the panel.
setup_remote_node() {
    local scheme="http" insecure=""
    echo
    ask PANEL_URL "URL of your panel (for example https://panel.example.com)" ""
    [[ "$PANEL_URL" =~ ^https?:// ]] || die "Please enter the full panel URL starting with http:// or https://"
    PANEL_URL="${PANEL_URL%/}"

    if [[ "$PANEL_URL" == https://* ]]; then
        scheme="https"
        echo "Your panel uses HTTPS, so Wings needs its own domain and certificate."
        ask NODE_FQDN "Domain of THIS machine (for example node1.example.com)" ""
        { [ -n "$NODE_FQDN" ] && ! is_ip "$NODE_FQDN"; } || die "A domain name (not an IP address) is required."
        ask LE_EMAIL "E-mail address for Let's Encrypt (expiry notices)" ""
        [[ "$LE_EMAIL" == *@*.* ]] || die "Please enter a valid e-mail address."
        obtain_certificate "$NODE_FQDN" "$LE_EMAIL"
    else
        insecure="--allow-insecure"
        ask NODE_FQDN "Address of THIS machine (IP or domain)" "$(public_ip)"
    fi

    echo
    printf '%s%s%s\n' "$C_BOLD" "Now create the node in the panel" "$C_RESET"
    echo "  Admin > Nodes > Create New, and enter:"
    echo "    FQDN:                    $NODE_FQDN"
    if [ "$scheme" = "https" ]; then
        echo "    Communicate Over SSL:    Use SSL Connection"
    else
        echo "    Communicate Over SSL:    Use HTTP Connection"
    fi
    echo "    Daemon Port / SFTP Port: 8080 / 2022"
    echo "  Then open the node's Configuration tab and click 'Generate Token'."
    echo

    ask WINGS_TOKEN "Token (shown after 'Generate Token', leave empty to do it later)" ""
    if [ -z "$WINGS_TOKEN" ]; then
        echo " Later: run the command from the Configuration tab on this machine, then: systemctl restart wings"
        return 0
    fi
    ask WINGS_NODE "Node ID (the number in the node's URL)" ""
    if (cd /etc/pterodactyl && /usr/local/bin/wings configure --panel-url "$PANEL_URL" --token "$WINGS_TOKEN" --node "$WINGS_NODE" $insecure); then
        if [ "$scheme" = "https" ] && ! grep -A2 'ssl:' /etc/pterodactyl/config.yml | grep -q 'enabled: true'; then
            warn "The node is set to HTTP in the panel. Edit it: Communicate Over SSL -> Use SSL Connection, then run this again."
        fi
        ok "Wings is configured"
        start_wings
    else
        warn "Configuring Wings failed. Check the URL, token and node ID, or copy the config from the Configuration tab to /etc/pterodactyl/config.yml."
    fi
}

install_wings() {
    local panel_here=0
    [ -f "$INSTALL_DIR/.env" ] && panel_here=1

    install_packages
    install_docker
    install_wings_binary
    open_firewall_ports 8080/tcp 2022/tcp

    local remote_url=""
    [ -f /etc/pterodactyl/config.yml ] && remote_url="$(awk '/^remote:/ {print $2; exit}' /etc/pterodactyl/config.yml | tr -d "\"'")"
    if [ "$panel_here" = "1" ]; then
        setup_local_node
    elif [ -n "$remote_url" ] && confirm "Wings is already connected to $remote_url. Keep that connection and only update Wings?" y; then
        # A node of a panel that was upgraded from Pterodactyl (or an earlier install): new Wings, same node.
        configure_wings_network
        start_wings
    else
        setup_remote_node
    fi

    # Database server for game servers (MC_GAME_DB=0 skips it in unattended installs).
    local with_db=1
    if [ -n "${MC_GAME_DB:-}" ]; then
        [ "$MC_GAME_DB" = "1" ] || with_db=0
    elif ! docker ps -a --format '{{.Names}}' | grep -qx "$GAMEDB_CONTAINER"; then
        echo
        confirm "Also set up a MySQL database server so game servers can get databases (plugins like LuckPerms need one)?" y || with_db=0
    fi
    if [ "$with_db" = "1" ] && setup_game_database; then
        if [ "$panel_here" = "1" ]; then
            register_game_database_local
            check_phpmyadmin --enable || warn "phpMyAdmin needs attention; run the installer again and choose [9] after fixing it."
        else
            print_game_database_details
        fi
    fi

    echo
    ok "Wings installation finished."
    echo " Game server ports are not created or opened automatically. Add them under"
    echo " Admin > Nodes > (your node) > Allocation, and allow them in your firewall yourself."
}

uninstall_wings() {
    [ -f /usr/local/bin/wings ] || die "Wings is not installed."
    confirm "Remove Wings? Servers stay in /var/lib/pterodactyl until you delete them." n || die "Aborted."
    systemctl disable --now wings >/dev/null 2>&1 || true
    rm -f /etc/systemd/system/wings.service /usr/local/bin/wings /etc/letsencrypt/renewal-hooks/deploy/recoded-ptero-wings.sh
    systemctl daemon-reload 2>/dev/null || true
    if confirm "Also delete /etc/pterodactyl (node configuration)?" n; then rm -rf /etc/pterodactyl; fi
    if docker ps -a --format '{{.Names}}' 2>/dev/null | grep -qx "$GAMEDB_CONTAINER" \
        && confirm "Also DELETE the database server for game servers and ALL databases in it?" n; then
        docker rm -f "$GAMEDB_CONTAINER" >/dev/null 2>&1
        docker volume rm recoded_gamedb >/dev/null 2>&1
        rm -f "$GAMEDB_ENV"
        ok "Database server for game servers removed."
    fi
    [ -f "$INSTALL_DIR/.env" ] || remove_cache_drop
    ok "Wings removed."
}

# ---------------------------------------------------------------- upgrade from Pterodactyl
#
# Moves an existing (non-Docker) Pterodactyl panel onto Recoded Ptero without losing data:
#  - the old database is only READ (dumped); it is never changed or deleted,
#  - the old panel directory, its .env and the nginx config are kept (plus backups of each),
#  - the copy is verified table by table before anything is switched over,
#  - on any error the old panel is switched back on automatically.
# Wings nodes keep working: the panel URL and the APP_KEY (which encrypts node tokens,
# 2FA secrets and database host passwords) stay the same.

OLD_DIR=""
UPG_BACKUP=""
UPG_DOWNTIME=0
UPG_NGINX_SWITCHED=0
UPG_PTEROQ_WAS_ACTIVE=0

old_env() {
    grep -m1 "^$1=" "$OLD_DIR/.env" 2>/dev/null | cut -d= -f2- | sed -e 's/[[:space:]]*$//' -e 's/^"\(.*\)"$/\1/' -e "s/^'\(.*\)'$/\1/"
}

old_mysql() {
    # Newer MariaDB clients print a warning about SSL verification for every query when the
    # password comes from MYSQL_PWD; keep those (and real errors) in the log, not on screen.
    MYSQL_PWD="$OLD_DB_PASS" "$MYSQL_BIN" -h "$OLD_DB_HOST" -P "$OLD_DB_PORT" -u "$OLD_DB_USER" "$@" 2>>"$INSTALL_LOG"
}

old_artisan() {
    (cd "$OLD_DIR" && php artisan "$@")
}

# Puts the old panel back exactly as it was. Safe to call at any point of the upgrade.
upgrade_rollback() {
    warn "Switching back to your Pterodactyl panel..."
    if [ "$UPG_NGINX_SWITCHED" = "1" ] && [ -f "$UPG_BACKUP/nginx-site.conf" ]; then
        cp -f "$UPG_BACKUP/nginx-site.conf" "$NGINX_SITE"
        nginx -t >/dev/null 2>&1 && { systemctl reload nginx 2>/dev/null || nginx -s reload 2>/dev/null; }
    fi
    [ -f "$COMPOSE_FILE" ] && dc stop >/dev/null 2>&1
    if [ "$UPG_DOWNTIME" = "1" ]; then
        [ -f "$UPG_BACKUP/crontab.txt" ] && crontab "$UPG_BACKUP/crontab.txt"
        [ "$UPG_PTEROQ_WAS_ACTIVE" = "1" ] && systemctl start pteroq >/dev/null 2>&1
        old_artisan up >/dev/null 2>&1
    fi
    ok "Your Pterodactyl panel runs exactly as before. Its database was never changed."
}

upgrade_fail() {
    printf '%s ✘ %s%s\n' "$C_RED" "$*" "$C_RESET" >&2
    upgrade_rollback
    [ -n "$UPG_BACKUP" ] && echo " Backups made before the upgrade: $UPG_BACKUP"
    exit 1
}

# Row count of every table, one "table count" line each.
table_counts_old() {
    local t
    for t in $(old_mysql -N -e "SHOW TABLES" "$OLD_DB_NAME"); do
        echo "$t $(old_mysql -N -e "SELECT COUNT(*) FROM \`$t\`" "$OLD_DB_NAME")"
    done
}

table_counts_new() {
    dc exec -T database sh -c 'for t in $(mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e "SHOW TABLES" panel); do echo "$t $(mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e "SELECT COUNT(*) FROM \`$t\`" panel)"; done' 2>/dev/null | tr -d '\r'
}

write_proxy_site() {
    local names="$1" cert="$2" key="$3" port="$4" v6="$5"
    {
        echo "# Recoded Ptero (upgraded from Pterodactyl on $(date -u +%Y-%m-%d))."
        echo "# The original file is saved in $UPG_BACKUP/nginx-site.conf"
        echo "server {"
        echo "    listen 80;"
        [ "$v6" = "1" ] && echo "    listen [::]:80;"
        echo "    server_name $names;"
        echo
        echo "    # Certificates are still renewed through this server block (webroot or nginx plugin)."
        echo "    location ^~ /.well-known/acme-challenge/ { root $OLD_DIR/public; }"
        if [ -n "$cert" ]; then
            echo '    location / { return 301 https://$host$request_uri; }'
            echo "}"
            echo
            echo "server {"
            echo "    listen 443 ssl;"
            [ "$v6" = "1" ] && echo "    listen [::]:443 ssl;"
            echo "    server_name $names;"
            echo "    ssl_certificate $cert;"
            echo "    ssl_certificate_key $key;"
            echo "    ssl_protocols TLSv1.2 TLSv1.3;"
            echo "    ssl_session_cache shared:RecodedPteroSSL:10m;"
        fi
        echo "    client_max_body_size 100m;"
        echo "    location / {"
        echo "        proxy_pass http://127.0.0.1:$port;"
        echo '        proxy_set_header Host $host;'
        echo '        proxy_set_header X-Real-IP $remote_addr;'
        echo '        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;'
        echo '        proxy_set_header X-Forwarded-Proto $scheme;'
        echo '        proxy_set_header X-Forwarded-Host $host;'
        echo "        proxy_read_timeout 300s;"
        echo "        proxy_request_buffering off;"
        echo "    }"
        echo "}"
    } > "$NGINX_SITE"
}

# SQL against Recoded Ptero's database (the statement comes on stdin, tab separated rows come back).
new_sql() {
    printf '%s\n' "$1" | dc exec -T database sh -c 'mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" -N -B panel' 2>>"$INSTALL_LOG" | tr -d '\r'
}

# Database hosts entered as 127.0.0.1 or localhost are MySQL on this machine. The old panel reached
# them directly; from inside Docker they are forwarded (hostdb service), so creating databases and
# phpMyAdmin keep working without changing MySQL, its users or the addresses shown to users.
# Returns 0 when it changed the configuration (the containers have to be recreated).
prepare_hostdb_relay() {
    local host port targets="" need_socket=0 socket="" candidate
    while IFS=$'\t' read -r host port; do
        host="$(echo "$host" | tr 'A-Z' 'a-z')"
        case "$host" in
            localhost) need_socket=1 ;;
            127.*)
                [[ "$host" =~ ^127\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ && "$port" =~ ^[0-9]{1,5}$ ]] \
                    && targets="$targets${targets:+,}$host:$port" ;;
        esac
    done < <(new_sql "SELECT host, port FROM database_hosts")
    targets="$(tr ',' '\n' <<<"$targets" | sed '/^$/d' | sort -u | paste -sd, -)"

    if [ "$need_socket" = "1" ]; then
        for candidate in /run/mysqld/mysqld.sock /var/run/mysqld/mysqld.sock /var/lib/mysql/mysql.sock /tmp/mysql.sock; do
            if [ -S "$candidate" ]; then socket="$(readlink -f "$candidate")"; break; fi
        done
        [ -n "$socket" ] || warn "A database host uses 'localhost', but no MySQL socket was found on this machine."
    fi
    [ -n "$targets" ] || [ -n "$socket" ] || return 1

    set_env_value HOSTDB_TARGETS "$targets"
    if [ -n "$socket" ]; then
        set_env_value HOSTDB_SOCKET_DIR "$(dirname "$socket")"
        set_env_value HOSTDB_SOCKET_NAME "$(basename "$socket")"
    fi
    set_env_value COMPOSE_PROFILES hostdb
    ok "Database hosts on this machine (${targets:+$targets}${targets:+${socket:+, }}${socket:+socket $socket}) are forwarded to the panel"
    return 0
}

# Wings on this machine (the usual single-server setup): which node it is. Sets LOCAL_NODE_ID,
# LOCAL_NODE_FQDN_VALUE, LOCAL_NODE_SCHEME and LOCAL_NODE_PORT; returns 1 when there is none.
detect_local_node() {
    local uuid row
    [ -x /usr/local/bin/wings ] && [ -f /etc/pterodactyl/config.yml ] || return 1
    uuid="$(awk '/^uuid:/ {print $2; exit}' /etc/pterodactyl/config.yml | tr -d "\"'")"
    [[ "$uuid" =~ ^[0-9a-fA-F-]{36}$ ]] || return 1
    row="$(new_sql "SELECT id, fqdn, scheme, daemonListen FROM nodes WHERE uuid = '$uuid' LIMIT 1")"
    [ -n "$row" ] || return 1
    IFS=$'\t' read -r LOCAL_NODE_ID LOCAL_NODE_FQDN_VALUE LOCAL_NODE_SCHEME LOCAL_NODE_PORT <<<"$row"
    [[ "$LOCAL_NODE_ID" =~ ^[0-9]+$ ]]
}

# Replaces the stock Wings on this machine with Recoded Ptero's build (graphs, crash reports,
# one-click updates), keeps its configuration and gives the node all of the machine's resources.
upgrade_local_wings() {
    info "Updating Wings on this machine to the Recoded Ptero build (game servers keep running)..."
    # In a subshell, so a failed download can't end the upgrade that already succeeded.
    if ( install_wings_binary ); then
        configure_wings_network
        start_wings
        check_node_connection "$LOCAL_NODE_SCHEME://$LOCAL_NODE_FQDN_VALUE:$LOCAL_NODE_PORT" || true
    else
        warn "Wings could not be updated now. Later: run this installer again and choose the Wings option."
    fi

    machine_limits
    new_sql "UPDATE nodes SET memory = GREATEST(memory, $mem_mb), disk = GREATEST(disk, $disk_mb) WHERE id = $LOCAL_NODE_ID" >/dev/null \
        && ok "Node limits: all of this machine's memory ($((mem_mb / 1024)) GB) and disk ($((disk_mb / 1024)) GB)"
}

# Everything a new installation sets up that an upgrade doesn't bring along by itself.
upgrade_finish() {
    local port="$1" recreate=0 local_node=0 id name fqdn remote=""

    prepare_hostdb_relay && recreate=1
    if detect_local_node; then
        local_node=1
        # The panel reaches this machine's Wings directly, like on a new installation.
        if ! is_ip "$LOCAL_NODE_FQDN_VALUE" && [ "$LOCAL_NODE_FQDN_VALUE" != "localhost" ]; then
            set_env_value LOCAL_NODE_FQDN "$LOCAL_NODE_FQDN_VALUE"
            recreate=1
        fi
    fi
    if [ "$recreate" = "1" ]; then
        run_step "Applying the new settings" "Settings applied" dc up -d || warn "Could not restart the services: recoded-ptero restart"
        run_step "Waiting for the panel" "Panel is running" wait_for_panel "$port" || warn "The panel takes long to start. Check: recoded-ptero logs panel"
    fi

    [ "${AUTO_UPDATE:-0}" = "1" ] && dc exec -T panel php artisan p:update:auto on >/dev/null 2>&1 && ok "Automatic updates enabled"

    [ "$local_node" = "1" ] && upgrade_local_wings

    # Like a new installation with Wings: a database server for game servers, if there is none yet.
    if [ "$local_node" = "1" ] && [ "$(new_sql 'SELECT COUNT(*) FROM database_hosts')" = "0" ]; then
        echo
        if confirm "Also set up a MySQL database server so game servers can get databases (plugins like LuckPerms need one)?" y \
            && setup_game_database; then
            NODE_FQDN="$LOCAL_NODE_FQDN_VALUE" NODE_ID="$LOCAL_NODE_ID" register_game_database_local
        fi
    fi

    # phpMyAdmin is part of the new panel; check it end to end, including a sign-in to every database host.
    echo
    info "Checking phpMyAdmin and the database hosts..."
    check_phpmyadmin --enable || warn "phpMyAdmin or a database host needs attention; run the installer again and choose [9] after fixing it."

    # Nodes on other machines need the new Wings too; the panel can't install it on stock Wings.
    while IFS=$'\t' read -r id name fqdn; do
        [ -n "$id" ] && remote="$remote     - $name ($fqdn)"$'\n'
    done < <(new_sql "SELECT id, name, fqdn FROM nodes WHERE id <> ${LOCAL_NODE_ID:-0} ORDER BY id")
    UPG_REMOTE_NODES="$remote"
}

upgrade_panel() {
    [ -f "$INSTALL_DIR/.env" ] && die "Recoded Ptero is already installed in $INSTALL_DIR."
    if [ -e "$INSTALL_DIR" ] && [ -n "$(ls -A "$INSTALL_DIR" 2>/dev/null)" ]; then
        die "$INSTALL_DIR already exists and is not empty."
    fi

    echo
    printf '%s%s%s\n' "$C_BOLD" "Upgrade Pterodactyl to Recoded Ptero" "$C_RESET"
    ask OLD_DIR "Where is your Pterodactyl panel installed?" "/var/www/pterodactyl"
    OLD_DIR="${OLD_DIR%/}"
    { [ -f "$OLD_DIR/artisan" ] && [ -f "$OLD_DIR/.env" ]; } || die "No Pterodactyl panel found in $OLD_DIR (artisan and .env are missing)."

    local old_version
    old_version="$(grep -oE "'version' => '[^']+'" "$OLD_DIR/config/app.php" 2>/dev/null | cut -d"'" -f4)"
    [[ "$old_version" == 1.* || "$old_version" == "canary" ]] \
        || die "Pterodactyl '${old_version:-unknown}' is not supported. Update it to 1.x first: https://pterodactyl.io/panel/1.0/updating.html"
    command -v php >/dev/null 2>&1 || die "php was not found; is this the server the panel runs on?"

    OLD_APP_URL="$(old_env APP_URL)"; OLD_APP_URL="${OLD_APP_URL%/}"
    OLD_DB_HOST="$(old_env DB_HOST)"; OLD_DB_HOST="${OLD_DB_HOST:-127.0.0.1}"
    OLD_DB_PORT="$(old_env DB_PORT)"; OLD_DB_PORT="${OLD_DB_PORT:-3306}"
    OLD_DB_NAME="$(old_env DB_DATABASE)"; OLD_DB_NAME="${OLD_DB_NAME:-panel}"
    OLD_DB_USER="$(old_env DB_USERNAME)"
    OLD_DB_PASS="$(old_env DB_PASSWORD)"
    [ -n "$(old_env APP_KEY)" ] || die "The old .env has no APP_KEY; without it the encrypted data can't be moved."
    [[ "$OLD_APP_URL" =~ ^https?:// ]] || die "APP_URL in $OLD_DIR/.env is missing or invalid."

    if command -v mariadb >/dev/null 2>&1; then MYSQL_BIN=mariadb; DUMP_BIN=mariadb-dump
    elif command -v mysql >/dev/null 2>&1; then MYSQL_BIN=mysql; DUMP_BIN=mysqldump
    else die "No MySQL/MariaDB client found on this server."
    fi
    command -v "$DUMP_BIN" >/dev/null 2>&1 || DUMP_BIN=mysqldump
    command -v "$DUMP_BIN" >/dev/null 2>&1 || die "No mysqldump/mariadb-dump found on this server."
    old_mysql -N -e "SELECT 1 FROM users LIMIT 1" "$OLD_DB_NAME" >/dev/null 2>&1 \
        || die "Could not read the panel database with the credentials from $OLD_DIR/.env."

    command -v nginx >/dev/null 2>&1 || die "Only panels served by nginx can be upgraded automatically. Nothing was changed."
    NGINX_SITE="$(grep -rlsE "root[[:space:]]+$OLD_DIR/public/?;" /etc/nginx/sites-enabled /etc/nginx/conf.d /etc/nginx/sites-available 2>/dev/null | head -n1)"
    [ -n "$NGINX_SITE" ] || die "Could not find the nginx config that serves $OLD_DIR. Nothing was changed."
    NGINX_SITE="$(readlink -f "$NGINX_SITE")"
    # The file is replaced as a whole, so it must not serve anything else.
    if grep -E '^[[:space:]]*root[[:space:]]' "$NGINX_SITE" | grep -vqE "$OLD_DIR/public/?;"; then
        die "$NGINX_SITE also serves other websites; move the panel into its own file first. Nothing was changed."
    fi

    local names cert key v6=0 users servers nodes port=8085
    names="$(grep -m1 -E '^[[:space:]]*server_name' "$NGINX_SITE" | sed -E 's/^[[:space:]]*server_name[[:space:]]+//; s/;.*$//')"
    [ -n "$names" ] || names="$(echo "$OLD_APP_URL" | sed -E 's#^https?://##; s#[:/].*$##')"
    cert="$(grep -m1 -E '^[[:space:]]*ssl_certificate[[:space:]]' "$NGINX_SITE" | awk '{print $2}' | tr -d ';')"
    key="$(grep -m1 -E '^[[:space:]]*ssl_certificate_key[[:space:]]' "$NGINX_SITE" | awk '{print $2}' | tr -d ';')"
    grep -qE 'listen[[:space:]]+\[::\]' "$NGINX_SITE" && v6=1
    { [ -z "$cert" ] || { [ -f "$cert" ] && [ -f "$key" ]; }; } || die "The certificate files named in $NGINX_SITE don't exist. Nothing was changed."
    while port_in_use "$port"; do port=$((port + 1)); done

    users="$(old_mysql -N -e "SELECT COUNT(*) FROM users" "$OLD_DB_NAME")"
    servers="$(old_mysql -N -e "SELECT COUNT(*) FROM servers" "$OLD_DB_NAME")"
    nodes="$(old_mysql -N -e "SELECT COUNT(*) FROM nodes" "$OLD_DB_NAME")"

    echo
    echo " Found Pterodactyl $old_version in $OLD_DIR"
    echo "   URL:       $OLD_APP_URL"
    echo "   Database:  $OLD_DB_NAME on $OLD_DB_HOST ($users users, $servers servers, $nodes nodes)"
    echo "   nginx:     $NGINX_SITE${cert:+ (HTTPS)}"
    echo
    echo " What happens:"
    echo "   1. Recoded Ptero is downloaded and built while your panel keeps running."
    echo "   2. Your panel goes into maintenance mode (a few minutes of downtime; game servers keep running)."
    echo "   3. Backups: database dump, .env, nginx config, crontab and the panel files."
    echo "   4. The database is copied into Recoded Ptero and checked table by table."
    echo "   5. nginx now forwards $OLD_APP_URL to Recoded Ptero. Same URL, same logins, Wings keep working."
    echo " Your old panel directory and database are NOT changed or deleted. If anything fails,"
    echo " the old panel is switched back on automatically."
    echo
    if [ "${MC_UPGRADE_CONFIRM:-}" != "yes" ]; then
        confirm "Start the upgrade?" n || die "Aborted, nothing was changed."
    fi
    # Same question as a new installation, asked now so the rest runs through in one go.
    AUTO_UPDATE=0
    confirm "Update the panel automatically whenever a new version is published?" n && AUTO_UPDATE=1

    install_packages
    install_docker
    ensure_swap

    run_step "Downloading Recoded Ptero" "Recoded Ptero downloaded" \
        git clone --quiet --branch "$GITHUB_BRANCH" "https://github.com/$GITHUB_REPO.git" "$INSTALL_DIR" \
        || die "Could not download the repository. Nothing was changed."
    local commit
    commit="$(git -C "$INSTALL_DIR" rev-parse HEAD)"
    git -C "$INSTALL_DIR" config core.fileMode false

    local old_name old_tz old_recaptcha
    old_name="$(old_env APP_NAME)"
    old_tz="$(old_env APP_TIMEZONE)"
    old_recaptcha="$(old_env RECAPTCHA_ENABLED)"
    (
        umask 077
        cat > "$INSTALL_DIR/.env" <<EOF
# Written by install.sh (upgrade from Pterodactyl in $OLD_DIR). Contains secrets, keep it private.
INSTALL_DIR=$INSTALL_DIR
COMPOSE_PROJECT_NAME=recodedptero
MC_PANEL_REPO=$GITHUB_REPO
MC_PANEL_BRANCH=$GITHUB_BRANCH
APP_URL=$OLD_APP_URL
APP_NAME="${old_name:-Recoded Ptero}"
APP_TIMEZONE=${old_tz:-$(system_timezone)}
APP_SERVICE_AUTHOR=$(old_env APP_SERVICE_AUTHOR)
DB_PASSWORD=$(random_string 32)
DB_ROOT_PASSWORD=$(random_string 32)
HTTP_BIND=127.0.0.1
HTTP_PORT=$port
HTTPS_PORT=$((port + 1000))
LE_EMAIL=
TRUSTED_PROXIES=*
# Same login protection as before (stock Pterodactyl enables reCAPTCHA by default).
RECAPTCHA_ENABLED=${old_recaptcha:-true}
EOF
    )
    mkdir -p "$INSTALL_DIR/state" "$INSTALL_DIR/backups"
    ln -sf "$INSTALL_DIR/installer/recoded-ptero" /usr/local/bin/recoded-ptero
    chmod +x "$INSTALL_DIR/installer/recoded-ptero" "$INSTALL_DIR/installer/updater/updater.sh"

    run_step "Building the updater" "Updater built" dc build updater || die "Could not build the updater, your panel was not touched."
    run_step "Building Recoded Ptero (this can take a few minutes, your panel keeps running)" "Recoded Ptero built" \
        dc build --build-arg "MC_COMMIT=$commit" panel || die "The build failed, your panel was not touched."

    # ------------------------------------------------------------ downtime starts here
    UPG_BACKUP="$INSTALL_DIR/backups/pterodactyl-$(date -u +%Y%m%d-%H%M%S)"
    mkdir -p "$UPG_BACKUP"; chmod 700 "$UPG_BACKUP"
    cp -p "$OLD_DIR/.env" "$UPG_BACKUP/old.env"
    cp -p "$NGINX_SITE" "$UPG_BACKUP/nginx-site.conf"
    crontab -l > "$UPG_BACKUP/crontab.txt" 2>/dev/null || : > "$UPG_BACKUP/crontab.txt"

    info "Putting your Pterodactyl panel into maintenance mode..."
    UPG_DOWNTIME=1
    old_artisan down >/dev/null 2>&1 || upgrade_fail "Could not put the old panel into maintenance mode."
    if systemctl is-active --quiet pteroq 2>/dev/null; then
        UPG_PTEROQ_WAS_ACTIVE=1
        systemctl stop pteroq >/dev/null 2>&1
    fi
    # Its scheduler must not run next to the new one (server schedules would fire twice).
    if grep -q "$OLD_DIR/artisan" "$UPG_BACKUP/crontab.txt"; then
        sed "s|^\([^#].*$OLD_DIR/artisan.*\)$|# disabled by Recoded Ptero upgrade: \1|" "$UPG_BACKUP/crontab.txt" | crontab - \
            || upgrade_fail "Could not pause the old panel's cron job."
    fi
    ok "Pterodactyl is in maintenance mode, its queue worker and cron job are paused"

    info "Backing up the old database..."
    local dump="$UPG_BACKUP/database.sql.gz"
    MYSQL_PWD="$OLD_DB_PASS" "$DUMP_BIN" -h "$OLD_DB_HOST" -P "$OLD_DB_PORT" -u "$OLD_DB_USER" \
        --single-transaction --quick --no-tablespaces --default-character-set=utf8mb4 "$OLD_DB_NAME" 2>"$UPG_BACKUP/dump.err" | gzip > "$dump"
    [ "${PIPESTATUS[0]}" -eq 0 ] || upgrade_fail "The database dump failed: $(head -c 300 "$UPG_BACKUP/dump.err")"
    gzip -dc "$dump" | grep -q 'CREATE TABLE `users`' || upgrade_fail "The database dump looks incomplete."
    ok "Database backup: $dump ($(du -h "$dump" | cut -f1))"
    tar czf "$UPG_BACKUP/panel-files.tar.gz" -C "$(dirname "$OLD_DIR")" --exclude="$(basename "$OLD_DIR")/vendor" \
        --exclude="$(basename "$OLD_DIR")/node_modules" "$(basename "$OLD_DIR")" 2>/dev/null
    ok "Panel files backup: $UPG_BACKUP/panel-files.tar.gz"
    table_counts_old > "$UPG_BACKUP/row-counts-old.txt" || upgrade_fail "Could not count the rows of the old database."

    info "Copying the database into Recoded Ptero..."
    dc up --no-start >/dev/null 2>&1 || upgrade_fail "Could not create the containers."
    # Keep APP_KEY (decrypts node tokens, 2FA secrets...), hashids salt, mail, reCAPTCHA and backup
    # settings. Database and Redis settings come from the new stack.
    grep -E '^(APP_KEY|APP_LOCALE|APP_THEME|HASHIDS_SALT|HASHIDS_LENGTH|MAIL_[A-Z_]+|RECAPTCHA_[A-Z_]+|AWS_[A-Z_]+|BACKUP_[A-Z_]+|APP_BACKUP_DRIVER|PTERODACTYL_[A-Z_]+|SESSION_LIFETIME)=' \
        "$OLD_DIR/.env" > "$UPG_BACKUP/panel-var.env"
    docker run --rm --entrypoint sh -v recodedptero_panel_var:/v -v "$UPG_BACKUP/panel-var.env:/src.env:ro" recodedptero-panel:latest \
        -c 'cp /src.env /v/.env && chmod 644 /v/.env' || upgrade_fail "Could not write the panel settings."

    dc up -d database cache >/dev/null 2>&1 || upgrade_fail "Could not start the database."
    local tries=0
    until dc exec -T database sh -c 'mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SELECT 1" panel' >/dev/null 2>&1; do
        tries=$((tries + 1)); [ "$tries" -ge 60 ] && upgrade_fail "The new database did not start."
        sleep 3
    done
    # MySQL 8 collations that older MariaDB versions don't know are mapped to their equivalent.
    gzip -dc "$dump" | sed -e 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g' \
        | dc exec -T database sh -c 'mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" panel' 2>"$UPG_BACKUP/import.err" \
        || upgrade_fail "Importing the database failed: $(head -c 300 "$UPG_BACKUP/import.err")"

    table_counts_new > "$UPG_BACKUP/row-counts-new.txt"
    if ! diff -q <(sort "$UPG_BACKUP/row-counts-old.txt") <(sort "$UPG_BACKUP/row-counts-new.txt") >/dev/null; then
        diff <(sort "$UPG_BACKUP/row-counts-old.txt") <(sort "$UPG_BACKUP/row-counts-new.txt") | head -n 20
        upgrade_fail "The copied database does not match the original (see above)."
    fi
    ok "Database copied and verified: $(wc -l < "$UPG_BACKUP/row-counts-old.txt") tables, every row count matches"

    info "Starting Recoded Ptero (database updates run now)..."
    dc up -d >/dev/null 2>&1 || upgrade_fail "Could not start Recoded Ptero."
    local waited=0 code
    while [ "$waited" -lt 420 ]; do
        code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 "http://127.0.0.1:$port/auth/login" 2>/dev/null || true)"
        if [ -n "$code" ] && [ "$code" != "000" ] && [ "$code" -lt 500 ]; then break; fi
        sleep 5; waited=$((waited + 5))
    done
    [ "$waited" -lt 420 ] || { dc logs --tail 40 panel; upgrade_fail "Recoded Ptero did not start in time (logs above)."; }
    local new_users
    new_users="$(dc exec -T database sh -c 'mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e "SELECT COUNT(*) FROM users" panel' 2>/dev/null | tr -d '\r')"
    [ "$new_users" = "$users" ] || upgrade_fail "User count changed during the database update ($users -> $new_users)."
    ok "Recoded Ptero is running with all $users users, $servers servers and $nodes nodes"

    info "Switching nginx over to Recoded Ptero..."
    UPG_NGINX_SWITCHED=1
    write_proxy_site "$names" "$cert" "$key" "$port" "$v6"
    nginx -t >"$UPG_BACKUP/nginx-test.txt" 2>&1 || upgrade_fail "The new nginx config is invalid: $(tail -n 3 "$UPG_BACKUP/nginx-test.txt")"
    { systemctl reload nginx 2>/dev/null || nginx -s reload 2>/dev/null; } || upgrade_fail "nginx could not be reloaded."
    sleep 2
    local host="${OLD_APP_URL#*://}"; host="${host%%/*}"; host="${host%%:*}"
    local scheme_port=80; [[ "$OLD_APP_URL" == https://* ]] && scheme_port=443
    code="$(curl -sk -o /dev/null -w '%{http_code}' --max-time 10 --resolve "$host:$scheme_port:127.0.0.1" "$OLD_APP_URL/auth/login" 2>/dev/null || true)"
    { [ -n "$code" ] && [ "$code" != "000" ] && [ "$code" -lt 500 ]; } || upgrade_fail "$OLD_APP_URL does not answer through nginx (HTTP $code)."
    ok "nginx now serves Recoded Ptero at $OLD_APP_URL"

    # Done. The old panel stays in maintenance mode, its queue and cron stay off.
    UPG_DOWNTIME=0; UPG_NGINX_SWITCHED=0
    cat > "$UPG_BACKUP/rollback.sh" <<EOF
#!/usr/bin/env bash
# Switches back to the old Pterodactyl panel. Changes made in Recoded Ptero after the upgrade
# are NOT in the old database (they stay in Recoded Ptero's database).
cp -f "$UPG_BACKUP/nginx-site.conf" "$NGINX_SITE" && nginx -t && (systemctl reload nginx || nginx -s reload)
docker compose -f "$COMPOSE_FILE" --project-directory "$INSTALL_DIR" stop
crontab "$UPG_BACKUP/crontab.txt"
[ "$UPG_PTEROQ_WAS_ACTIVE" = "1" ] && systemctl start pteroq
cd "$OLD_DIR" && php artisan up
EOF
    chmod 700 "$UPG_BACKUP/rollback.sh"

    # The panel is moved; now the rest of what a new installation has (none of it can undo the move).
    echo
    info "Setting up everything a new Recoded Ptero installation has..."
    UPG_REMOTE_NODES=""
    upgrade_finish "$port"

    local owner
    owner="$(dc exec -T database sh -c 'mariadb -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e "SELECT username FROM users WHERE role=\"owner\" LIMIT 1" panel' 2>/dev/null | tr -d '\r')"

    echo
    printf '%s%s%s\n' "$C_GREEN" "======================================================" "$C_RESET"
    printf '%s%s%s\n' "$C_GREEN$C_BOLD" " Upgrade to Recoded Ptero finished" "$C_RESET"
    printf '%s%s%s\n' "$C_GREEN" "======================================================" "$C_RESET"
    echo " URL:        $OLD_APP_URL (unchanged, log in as before)"
    echo " Owner:      ${owner:-first admin} (admins are now 'admin', the first admin is 'owner')"
    echo " Data:       $users users, $servers servers, $nodes nodes, all tables verified"
    echo " Backups:    $UPG_BACKUP"
    echo " Old panel:  $OLD_DIR and its database are untouched (maintenance mode, queue and cron off)."
    echo "             Back to Pterodactyl if ever needed: bash $UPG_BACKUP/rollback.sh"
    if [ -n "$UPG_REMOTE_NODES" ]; then
        echo
        echo " Nodes on other machines still run the stock Wings (no graphs, crash reports or"
        echo " one-click updates yet). On each of them, run this installer once, choose the"
        echo " Wings option and keep the existing connection:"
        printf '%s' "$UPG_REMOTE_NODES"
        echo "   bash <(curl -sSL https://raw.githubusercontent.com/$GITHUB_REPO/$GITHUB_BRANCH/install.sh)"
    fi
    echo
    echo " Handy commands:  recoded-ptero status | update | logs | backup | restart"
    echo
}

# ---------------------------------------------------------------- phpMyAdmin and databases
#
# Runs the panel's own check (p:phpmyadmin:check): phpMyAdmin installed in the image, switched on, served
# by the web server, and a real sign-in through the ticket flow to every database host. Prints it as a
# checklist; returns 1 when something failed. Extra arguments go to the command (for example --enable).
check_phpmyadmin() {
    local out status check detail addr text version="" base_ok=1 failed=0
    out="$(dc exec -T panel php artisan p:phpmyadmin:check "$@" 2>>"$INSTALL_LOG" | tr -d '\r')"
    if [ -z "$out" ]; then
        warn "The phpMyAdmin check gave no result. See: recoded-ptero logs panel"
        return 1
    fi
    # Short on purpose: one line when phpMyAdmin itself is fine, one line per database host.
    while IFS='|' read -r status check detail; do
        case "$check" in
            image) version="$detail"; [ "$status" = "ok" ] || { warn "${detail:0:110}"; base_ok=0; failed=1; } ;;
            enabled) [ "$status" = "ok" ] || { warn "phpMyAdmin is $detail"; base_ok=0; failed=1; } ;;
            web) [ "$status" = "ok" ] || { warn "The web server does not serve phpMyAdmin correctly: ${detail:0:110}"; base_ok=0; failed=1; } ;;
        esac
    done <<<"$out"
    [ "$base_ok" = "1" ] && ok "$version is installed, switched on and reachable"
    while IFS='|' read -r status check detail; do
        case "$check" in
            hosts) info "$detail" ;;
            host)
                addr="${detail%%|*}"; text="${detail#*|}"
                if [ "$status" = "ok" ]; then
                    ok "Database host ${addr#*:} works"
                else
                    warn "Database host ${addr#*:} fails: ${text:0:110}"; failed=1
                fi
                ;;
        esac
    done <<<"$out"
    [ "$failed" = "1" ] && echo "     More detail: recoded-ptero artisan p:phpmyadmin:check"

    return "$failed"
}

# Sets up everything around the databases of game servers and checks it: phpMyAdmin is part of the
# installed panel (the panel is updated when it is not), a database server for game servers exists
# and is registered in the panel, hosts on this machine are reachable, and phpMyAdmin really signs in.
setup_databases() {
    [ -f "$INSTALL_DIR/.env" ] || die "Recoded Ptero is not installed in $INSTALL_DIR. Install it first (option [1] or [4])."
    local port hosts
    port="$(env_value HTTP_PORT)"; port="${port:-80}"

    dc up -d >/dev/null 2>&1 || die "Could not start the panel. Check: recoded-ptero logs panel"
    run_step "Waiting for the panel" "Panel is running" wait_for_panel "$port" \
        || die "The panel did not come up in time. Check: recoded-ptero logs panel"

    # 1. phpMyAdmin comes with the panel image (older versions don't have it).
    if dc exec -T panel test -f /app/public/phpmyadmin/signon.php; then
        ok "phpMyAdmin is part of the installed panel"
    else
        warn "The installed panel version does not include phpMyAdmin yet."
        run_step "Updating the panel (phpMyAdmin comes with the newest version)" "Panel updated" \
            bash "$INSTALL_DIR/installer/updater/updater.sh" run "databases-$(date +%s)" false 0 \
            || die "The update failed. Try: recoded-ptero update"
        run_step "Waiting for the panel" "Panel is running" wait_for_panel "$port" \
            || die "The panel did not come up in time. Check: recoded-ptero logs panel"
        dc exec -T panel test -f /app/public/phpmyadmin/signon.php || die "phpMyAdmin is still missing after the update."
        ok "phpMyAdmin is installed"
    fi

    # 2. A database server for game servers, registered in the panel.
    hosts="$(new_sql 'SELECT COUNT(*) FROM database_hosts')"
    if [ "${hosts:-0}" = "0" ]; then
        info "No database host is set up in the panel yet."
        if detect_local_node; then
            if confirm "Set up a MySQL database server for game servers on this machine and add it to the panel?" y \
                && setup_game_database; then
                NODE_FQDN="$LOCAL_NODE_FQDN_VALUE" NODE_ID="$LOCAL_NODE_ID" register_game_database_local
            fi
        else
            warn "There is no Wings node on this machine, so the database server can't be created here."
            echo "     Run this on the machine that runs Wings (Wings option [3] offers it there), or add a database"
            echo "     host yourself under Admin > Databases > Create New."
        fi
    else
        ok "$hosts database host(s) registered in the panel"
    fi

    # 3. Hosts on this machine (127.0.0.1 / localhost) need the forwarding into the container.
    if prepare_hostdb_relay; then
        run_step "Applying the settings" "Settings applied" dc up -d || warn "Could not restart the services: recoded-ptero restart"
        run_step "Waiting for the panel" "Panel is running" wait_for_panel "$port" || warn "The panel takes long to start. Check: recoded-ptero logs panel"
    fi

    # 4. Does it all work, including a real sign-in to phpMyAdmin?
    echo
    info "Checking phpMyAdmin and every database host..."
    if check_phpmyadmin --enable; then
        ok "phpMyAdmin works: open it under Admin > Databases, or from a server's Databases tab."
    else
        warn "Something needs attention (see above). Run this option again after fixing it."
        return 1
    fi
}

# ---------------------------------------------------------------- go back to Pterodactyl
#
# An upgrade leaves the old Pterodactyl panel (files and database) untouched and saves what is needed
# to switch back (rollback.sh, nginx site, crontab) in its backup folder. This is the guided version:
# it can carry over what happened in Recoded Ptero since the upgrade, puts the old panel back (nginx,
# queue worker, cron job) and stops Recoded Ptero. Nothing is deleted; Recoded Ptero's containers and
# data stay until you uninstall it. Every step that could fail restores the previous state.

REV_BACKUP=""
REV_MAINT=0

revert_fail() {
    printf '%s ✘ %s%s\n' "$C_RED" "$*" "$C_RESET" >&2
    [ "$REV_MAINT" = "1" ] && dc exec -T panel php artisan up >/dev/null 2>&1
    dc up -d updater >/dev/null 2>&1
    [ -n "$REV_BACKUP" ] && echo " Backups made before the revert: $REV_BACKUP" >&2
    echo " Recoded Ptero keeps running as before; nothing was switched." >&2
    exit 1
}

# The upgrade backup to go back to: the newest one that has everything needed.
revert_find_backup() {
    local dir
    for dir in $(ls -dt "$INSTALL_DIR"/backups/pterodactyl-*/ 2>/dev/null); do
        dir="${dir%/}"
        if [ -f "$dir/rollback.sh" ] && [ -f "$dir/old.env" ] && [ -f "$dir/nginx-site.conf" ] && [ -f "$dir/crontab.txt" ]; then
            UPG_BACKUP="$dir"
            return 0
        fi
    done

    return 1
}

# artisan of the old panel as the user who owns its files (root would leave root-owned cache files behind).
old_artisan_owner() {
    local owner
    owner="$(stat -c %U "$OLD_DIR" 2>/dev/null)"
    if [ -n "$owner" ] && [ "$owner" != "root" ] && command -v runuser >/dev/null 2>&1; then
        (cd "$OLD_DIR" && runuser -u "$owner" -- php artisan "$@")
    else
        (cd "$OLD_DIR" && php artisan "$@")
    fi
}

# MariaDB dumps carry MariaDB-only collations that MySQL 8 doesn't know.
revert_convert_dump() {
    sed -e 's/utf8mb4_uca1400_[A-Za-z0-9_]*/utf8mb4_unicode_ci/g' -e 's/utf8mb3_uca1400_[A-Za-z0-9_]*/utf8_unicode_ci/g'
}

revert_restore_old_db() {
    [ -s "$REV_BACKUP/pterodactyl-database.sql.gz" ] || return 0
    warn "Restoring the Pterodactyl database as it was..."
    gzip -dc "$REV_BACKUP/pterodactyl-database.sql.gz" | old_mysql "$OLD_DB_NAME" \
        || warn "Could not restore it automatically; the dump is $REV_BACKUP/pterodactyl-database.sql.gz"
}

revert_panel() {
    [ -f "$INSTALL_DIR/.env" ] || die "Recoded Ptero is not installed in $INSTALL_DIR."
    revert_find_backup || die "There is no earlier Pterodactyl panel to go back to: this panel was installed fresh, or the upgrade backup in $INSTALL_DIR/backups is gone. To run the official Pterodactyl instead, install it following https://pterodactyl.io/panel/1.0/getting_started.html and restore a database dump into it."

    OLD_DIR="$(sed -n 's/^cd "\(.*\)" && php artisan up$/\1/p' "$UPG_BACKUP/rollback.sh" | head -n1)"
    NGINX_SITE="$(sed -n 's/^cp -f "[^"]*" "\([^"]*\)" && nginx -t.*/\1/p' "$UPG_BACKUP/rollback.sh" | head -n1)"
    UPG_PTEROQ_WAS_ACTIVE=0
    grep -q '^\[ "1" = "1" \] && systemctl start pteroq' "$UPG_BACKUP/rollback.sh" && UPG_PTEROQ_WAS_ACTIVE=1
    { [ -n "$OLD_DIR" ] && [ -f "$OLD_DIR/artisan" ] && [ -f "$OLD_DIR/.env" ]; } || die "The old Pterodactyl panel was not found in '${OLD_DIR:-?}' (artisan or .env is missing); it can't be switched back."
    [ -n "$NGINX_SITE" ] || die "The nginx site of the old panel could not be read from $UPG_BACKUP/rollback.sh."
    command -v php >/dev/null 2>&1 || die "php was not found, which the old panel needs."
    command -v nginx >/dev/null 2>&1 || die "nginx was not found, which served the old panel."

    OLD_DB_HOST="$(old_env DB_HOST)"; OLD_DB_HOST="${OLD_DB_HOST:-127.0.0.1}"
    OLD_DB_PORT="$(old_env DB_PORT)"; OLD_DB_PORT="${OLD_DB_PORT:-3306}"
    OLD_DB_NAME="$(old_env DB_DATABASE)"; OLD_DB_NAME="${OLD_DB_NAME:-panel}"
    OLD_DB_USER="$(old_env DB_USERNAME)"
    OLD_DB_PASS="$(old_env DB_PASSWORD)"
    OLD_APP_URL="$(old_env APP_URL)"; OLD_APP_URL="${OLD_APP_URL%/}"
    if command -v mariadb >/dev/null 2>&1; then MYSQL_BIN=mariadb; DUMP_BIN=mariadb-dump
    elif command -v mysql >/dev/null 2>&1; then MYSQL_BIN=mysql; DUMP_BIN=mysqldump
    else die "No MySQL/MariaDB client found on this server."
    fi
    command -v "$DUMP_BIN" >/dev/null 2>&1 || DUMP_BIN=mysqldump
    command -v "$DUMP_BIN" >/dev/null 2>&1 || die "No mysqldump/mariadb-dump found on this server."
    old_mysql -N -e "SELECT 1 FROM users LIMIT 1" "$OLD_DB_NAME" >/dev/null 2>&1 \
        || die "Could not read the Pterodactyl database ($OLD_DB_NAME) with the credentials from $OLD_DIR/.env."
    dc ps >/dev/null 2>&1 || die "Docker is not running."
    dc up -d >/dev/null 2>&1 || die "Could not start Recoded Ptero to read its data."

    # What can be carried over: only if the old panel's code is not older than the database changes it needs.
    local ahead mode="${MC_REVERT_MODE:-}" carry_ok=1
    ahead="$(comm -13 <(old_mysql -N -e "SELECT migration FROM migrations" "$OLD_DB_NAME" | sort) <(new_sql "SELECT migration FROM migrations" | sort) | grep -v '^2026_' | head -n3 | tr '\n' ' ')"
    if [ -n "$ahead" ] && [ "${MC_REVERT_FORCE:-0}" != "1" ]; then
        carry_ok=0
    fi

    echo
    printf '%s%s%s\n' "$C_BOLD" "Go back to the normal Pterodactyl panel" "$C_RESET"
    echo " Found your Pterodactyl panel in $OLD_DIR (nginx: $NGINX_SITE)."
    echo " Backup of the upgrade: $UPG_BACKUP"
    echo
    echo " What happens:"
    echo "   1. Recoded Ptero goes into maintenance mode (a few minutes; game servers keep running)."
    echo "   2. Backups: Recoded Ptero's database and the Pterodactyl database."
    echo "   3. Optionally the data of Recoded Ptero is copied into the Pterodactyl database."
    echo "   4. nginx, the queue worker and the cron job of Pterodactyl are switched back on."
    echo "   5. Recoded Ptero is stopped (its containers and data are kept, nothing is deleted)."
    echo " The panel keeps its address, logins and Wings connections. Recoded Ptero's extra features"
    echo " (version changer, subdomains, tickets, coins, ...) are gone in Pterodactyl; their data stays in the database."
    echo
    if [ "$carry_ok" = "0" ]; then
        warn "Recoded Ptero's database has newer Pterodactyl changes than your old panel knows: $ahead"
        warn "Carrying the data over could break the old panel, so only the exact old state is offered."
        mode=2
    fi
    if [ -z "$mode" ]; then
        echo " [1] Carry over everything done in Recoded Ptero since the upgrade (new users, servers, settings, ...)"
        echo " [2] Go back to the exact state at the time of the upgrade (changes since then stay only in the backup)"
        ask mode "Choose 1 or 2" "1"
    fi
    case "$mode" in 1|carry) mode=carry ;; 2|exact) mode=exact ;; *) die "Please choose 1 or 2." ;; esac
    if [ "${MC_REVERT_CONFIRM:-}" != "yes" ]; then
        confirm "Go back to Pterodactyl now?" n || die "Aborted, nothing was changed."
    fi

    REV_BACKUP="$INSTALL_DIR/backups/before-revert-$(date -u +%Y%m%d-%H%M%S)"
    mkdir -p "$REV_BACKUP"; chmod 700 "$REV_BACKUP"

    # ------------------------------------------------------------ nothing is switched before this point works
    dc stop updater >/dev/null 2>&1
    info "Putting Recoded Ptero into maintenance mode..."
    dc exec -T panel php artisan down >/dev/null 2>&1 || revert_fail "Could not put Recoded Ptero into maintenance mode."
    REV_MAINT=1

    info "Backing up both databases..."
    dc exec -T database sh -c 'mariadb-dump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --quick --no-tablespaces --default-character-set=utf8mb4 panel' \
        2>"$REV_BACKUP/dump.err" | gzip > "$REV_BACKUP/recoded-ptero-database.sql.gz"
    [ "${PIPESTATUS[0]}" -eq 0 ] || revert_fail "The dump of Recoded Ptero's database failed: $(head -c 300 "$REV_BACKUP/dump.err")"
    gzip -dc "$REV_BACKUP/recoded-ptero-database.sql.gz" | grep -q 'CREATE TABLE `users`' || revert_fail "The dump of Recoded Ptero's database looks incomplete."
    MYSQL_PWD="$OLD_DB_PASS" "$DUMP_BIN" -h "$OLD_DB_HOST" -P "$OLD_DB_PORT" -u "$OLD_DB_USER" \
        --single-transaction --quick --no-tablespaces --default-character-set=utf8mb4 "$OLD_DB_NAME" 2>"$REV_BACKUP/dump-old.err" | gzip > "$REV_BACKUP/pterodactyl-database.sql.gz"
    [ "${PIPESTATUS[0]}" -eq 0 ] || revert_fail "The dump of the Pterodactyl database failed: $(head -c 300 "$REV_BACKUP/dump-old.err")"
    gzip -dc "$REV_BACKUP/pterodactyl-database.sql.gz" | grep -q 'CREATE TABLE `users`' || revert_fail "The dump of the Pterodactyl database looks incomplete."
    cp -p "$NGINX_SITE" "$REV_BACKUP/nginx-site-recoded.conf"
    crontab -l > "$REV_BACKUP/crontab-before.txt" 2>/dev/null || : > "$REV_BACKUP/crontab-before.txt"
    ok "Backups saved in $REV_BACKUP"

    local users servers nodes
    if [ "$mode" = "carry" ]; then
        users="$(new_sql 'SELECT COUNT(*) FROM users')"; servers="$(new_sql 'SELECT COUNT(*) FROM servers')"; nodes="$(new_sql 'SELECT COUNT(*) FROM nodes')"
        info "Copying the data of Recoded Ptero into the Pterodactyl database..."
        if ! gzip -dc "$REV_BACKUP/recoded-ptero-database.sql.gz" | revert_convert_dump | old_mysql "$OLD_DB_NAME"; then
            revert_restore_old_db
            revert_fail "Importing the data failed (details in $INSTALL_LOG)."
        fi
        if [ "$(old_mysql -N -e 'SELECT COUNT(*) FROM users' "$OLD_DB_NAME")" != "$users" ] \
            || [ "$(old_mysql -N -e 'SELECT COUNT(*) FROM servers' "$OLD_DB_NAME")" != "$servers" ] \
            || [ "$(old_mysql -N -e 'SELECT COUNT(*) FROM nodes' "$OLD_DB_NAME")" != "$nodes" ]; then
            revert_restore_old_db
            revert_fail "The copied data does not match (users/servers/nodes counts differ)."
        fi
        if ! old_artisan_owner migrate --force >>"$INSTALL_LOG" 2>&1; then
            revert_restore_old_db
            revert_fail "The Pterodactyl panel could not use the copied database (php artisan migrate failed, see $INSTALL_LOG)."
        fi
        ok "Copied: $users users, $servers servers, $nodes nodes, all counts match"
    fi
    old_artisan_owner config:clear >/dev/null 2>&1; old_artisan_owner cache:clear >/dev/null 2>&1; old_artisan_owner view:clear >/dev/null 2>&1

    # ------------------------------------------------------------ switching
    info "Switching nginx, the queue worker and the cron job back to Pterodactyl..."
    cp -f "$UPG_BACKUP/nginx-site.conf" "$NGINX_SITE"
    if ! nginx -t >"$REV_BACKUP/nginx-test.txt" 2>&1; then
        cp -f "$REV_BACKUP/nginx-site-recoded.conf" "$NGINX_SITE"
        [ "$mode" = "carry" ] && revert_restore_old_db
        revert_fail "The nginx config of the old panel is invalid: $(tail -n 3 "$REV_BACKUP/nginx-test.txt")"
    fi
    { systemctl reload nginx 2>/dev/null || nginx -s reload 2>/dev/null; } || { cp -f "$REV_BACKUP/nginx-site-recoded.conf" "$NGINX_SITE"; revert_fail "nginx could not be reloaded."; }
    crontab "$UPG_BACKUP/crontab.txt" 2>/dev/null
    [ "$UPG_PTEROQ_WAS_ACTIVE" = "1" ] && systemctl start pteroq >/dev/null 2>&1
    old_artisan_owner up >/dev/null 2>&1
    sleep 2

    local host scheme_port=80 code
    host="${OLD_APP_URL#*://}"; host="${host%%/*}"; host="${host%%:*}"
    [[ "$OLD_APP_URL" == https://* ]] && scheme_port=443
    code="$(curl -sk -o /dev/null -w '%{http_code}' --max-time 15 --resolve "$host:$scheme_port:127.0.0.1" "$OLD_APP_URL/auth/login" 2>/dev/null || true)"
    if ! { [ -n "$code" ] && [ "$code" != "000" ] && [ "$code" -lt 500 ]; }; then
        # Undo: Recoded Ptero's nginx site and cron state back, old panel off again.
        old_artisan_owner down >/dev/null 2>&1
        [ "$UPG_PTEROQ_WAS_ACTIVE" = "1" ] && systemctl stop pteroq >/dev/null 2>&1
        crontab "$REV_BACKUP/crontab-before.txt" 2>/dev/null
        cp -f "$REV_BACKUP/nginx-site-recoded.conf" "$NGINX_SITE"; { systemctl reload nginx 2>/dev/null || nginx -s reload 2>/dev/null; }
        [ "$mode" = "carry" ] && revert_restore_old_db
        revert_fail "$OLD_APP_URL does not answer with the Pterodactyl panel (HTTP $code); switched back to Recoded Ptero."
    fi
    ok "nginx serves your Pterodactyl panel at $OLD_APP_URL again"

    dc stop >/dev/null 2>&1
    REV_MAINT=0
    echo
    printf '%s%s%s\n' "$C_GREEN" "======================================================" "$C_RESET"
    printf '%s%s%s\n' "$C_GREEN$C_BOLD" " Back on the normal Pterodactyl panel" "$C_RESET"
    printf '%s%s%s\n' "$C_GREEN" "======================================================" "$C_RESET"
    echo " URL:        $OLD_APP_URL (unchanged, same logins)"
    if [ "$mode" = "carry" ]; then
        echo " Data:       everything done in Recoded Ptero since the upgrade was carried over ($users users, $servers servers, $nodes nodes)."
    else
        echo " Data:       the exact state of the upgrade. What changed in Recoded Ptero since is in the backup only."
    fi
    echo " Backups:    $REV_BACKUP and $UPG_BACKUP"
    echo " Recoded:    stopped, not deleted. Start it again: docker compose -f $COMPOSE_FILE up -d (it takes over nginx only after an upgrade)."
    echo "             To remove it completely run this installer and choose \"Uninstall Recoded Ptero\" (that deletes $INSTALL_DIR"
    echo "             including these backups; copy them elsewhere first)."
    echo " Wings:      your nodes keep working. They run Recoded Ptero's Wings build, which also works with Pterodactyl."
    echo
}

# ---------------------------------------------------------------- main

main() {
    require_root
    detect_system

    printf '\n%s%s%s\n' "$C_BOLD" "Recoded Ptero installer" "$C_RESET"
    echo "Source: github.com/$GITHUB_REPO ($GITHUB_BRANCH)"
    echo

    local action="${MC_ACTION:-}"
    if [ -z "$action" ]; then
        echo "What do you want to do?"
        echo "  [1] Install Recoded Ptero (asks whether Wings goes on this server too)"
        echo "  [2] Upgrade an existing Pterodactyl panel to Recoded Ptero (keeps all data)"
        echo "  [3] Install or repair Wings (game server daemon) on this machine"
        echo "  [4] Install Recoded Ptero and Wings on this machine in one go"
        echo "  [5] Update Recoded Ptero to the newest version"
        echo "  [6] Uninstall Recoded Ptero"
        echo "  [7] Uninstall Wings"
        echo "  [8] Go back to the normal Pterodactyl panel (after an upgrade from it)"
        echo "  [9] Set up and check databases and phpMyAdmin (installs/registers what is missing)"
        echo "  [0] Quit"
        ask CHOICE "Choose" ""
        case "$CHOICE" in
            1) action="panel" ;;
            2) action="upgrade" ;;
            3) action="wings" ;;
            4) action="both" ;;
            5) action="update" ;;
            6) action="uninstall-panel" ;;
            7) action="uninstall-wings" ;;
            8) action="revert" ;;
            9) action="databases" ;;
            *) exit 0 ;;
        esac
    fi

    case "$action" in
        panel)
            install_panel
            if [ "${WITH_WINGS:-0}" = "1" ]; then install_wings; fi
            ;;
        wings) install_wings ;;
        both) WITH_WINGS=1; install_panel; install_wings ;;
        upgrade) upgrade_panel ;;
        update) update_panel ;;
        uninstall-panel) uninstall_panel ;;
        uninstall-wings) uninstall_wings ;;
        revert) revert_panel ;;
        databases) setup_databases ;;
        *) die "Unknown action: $action" ;;
    esac

    # Everything that was installed or upgraded ends up on its newest version as well.
    case "$action" in
        panel|wings|both|upgrade)
            setup_cache_drop
            setup_security_updates
            if [ "${SYSTEM_UPGRADED:-0}" = "1" ]; then
                upgrade_system "Installing the last system updates" "System is up to date"
                reboot_notice
            fi
            ;;
    esac
}

# The whole script is defined before this line runs, so it is safe to pipe into bash too.
main "$@"; exit $?
