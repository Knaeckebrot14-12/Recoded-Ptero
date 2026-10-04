# Stage 0:
# Build the assets that are needed for the frontend. This build stage is then discarded
# since we won't need NodeJS anymore in the future. This Docker image ships a final production
# level distribution of Pterodactyl.
FROM --platform=$BUILDPLATFORM node:22-alpine
WORKDIR /app
# Dependencies first, so this slow layer is reused by every update that doesn't touch them.
COPY package.json yarn.lock ./
RUN yarn install --frozen-lockfile
COPY . ./
# The browser list only affects CSS prefixes; its "data is old" notice is just noise in the build log.
ENV BROWSERSLIST_IGNORE_OLD_DATA=1
# Old build tools use APIs Node now calls deprecated; the warnings change nothing about the result.
ENV NODE_OPTIONS=--no-deprecation
RUN yarn run build:production

# Stage 1:
# Build the actual container with all of the needed PHP dependencies that will run the application.
FROM --platform=$TARGETOS/$TARGETARCH php:8.3-fpm-alpine
WORKDIR /app

# System packages, PHP extensions and Composer come before the source is copied, so this slow
# part (several minutes of compiling) is reused by every update instead of being rebuilt.
RUN apk add --no-cache --update ca-certificates dcron curl git supervisor tar unzip nginx libpng-dev libxml2-dev libzip-dev certbot certbot-nginx mysql-client \
    && docker-php-ext-configure zip \
    && docker-php-ext-install bcmath gd pdo_mysql zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# phpMyAdmin at <panel>/phpmyadmin/ (signed in by the panel, see .github/docker/phpmyadmin). The
# official release is checked against the pinned SHA-256, which was compared with the release's
# GPG signature when the version was pinned. Its own layer, so the extension layer above stays
# cached on existing installations. Only the panel's languages are kept; documentation, setup
# and build files are removed, as is the "LOAD DATA LOCAL" import (it could read panel files).
ARG PHPMYADMIN_VERSION=5.2.3
ARG PHPMYADMIN_SHA256=12ba1c425fa4071abbd4e7668c9ebdeac0b0755a467a6d6d5026122bb47c102b
RUN docker-php-ext-install mysqli \
    && curl -fsSL -o /tmp/phpmyadmin.tar.gz "https://files.phpmyadmin.net/phpMyAdmin/${PHPMYADMIN_VERSION}/phpMyAdmin-${PHPMYADMIN_VERSION}-all-languages.tar.gz" \
    && echo "${PHPMYADMIN_SHA256}  /tmp/phpmyadmin.tar.gz" | sha256sum -c - \
    && mkdir -p /app/public/phpmyadmin \
    && tar -xzf /tmp/phpmyadmin.tar.gz -C /app/public/phpmyadmin --strip-components=1 --no-same-owner \
    && rm /tmp/phpmyadmin.tar.gz \
    && cd /app/public/phpmyadmin \
    && rm -rf setup doc examples test composer.json composer.lock package.json yarn.lock babel.config.json \
        CONTRIBUTING.md ChangeLog README RELEASE-DATE-* config.sample.inc.php show_config_errors.php .rtlcssrc.json robots.txt \
        libraries/classes/Plugins/Import/ImportLdi.php \
    && find locale -mindepth 1 -maxdepth 1 -type d ! -name de ! -name fr ! -name es ! -name it ! -name nl ! -name pl ! -name pt ! -name pt_BR ! -name ru -exec rm -rf {} + \
    && find . -name '*.map' -type f -delete \
    && mkdir -p /tmp/phpmyadmin/sessions /tmp/phpmyadmin/tmp \
    && chown -R nginx:nginx /tmp/phpmyadmin \
    && chmod -R 700 /tmp/phpmyadmin

RUN rm /usr/local/etc/php-fpm.conf \
    && echo "* * * * * /usr/local/bin/php /app/artisan schedule:run >> /dev/null 2>&1" >> /var/spool/cron/crontabs/root \
    && sed -i s/ssl_session_cache/#ssl_session_cache/g /etc/nginx/nginx.conf \
    && mkdir -p /var/run/php /var/run/nginx /var/www/acme

COPY .github/docker/default.conf /etc/nginx/http.d/default.conf
COPY .github/docker/security.conf /etc/nginx/recoded-ptero/security.conf
COPY .github/docker/www.conf /usr/local/etc/php-fpm.conf
COPY .github/docker/supervisord.conf /etc/supervisord.conf

# Database hosts on the machine itself after an upgrade from Pterodactyl (see hostdb-relay.sh).
# PHP's default MySQL socket ("localhost") points at the forwarded socket; unused otherwise.
RUN apk add --no-cache socat \
    && printf 'pdo_mysql.default_socket=/run/hostdb/mysqld.sock\nmysqli.default_socket=/run/hostdb/mysqld.sock\n' \
        > /usr/local/etc/php/conf.d/zz-hostdb.ini
COPY .github/docker/hostdb-relay.sh /usr/local/bin/hostdb-relay
RUN chmod 755 /usr/local/bin/hostdb-relay

COPY . ./
COPY --from=0 /app/public/assets ./public/assets
# phpMyAdmin stays owned by root (read-only for php-fpm), so it is left out of the chown below.
COPY .github/docker/phpmyadmin/config.inc.php .github/docker/phpmyadmin/signon.php ./public/phpmyadmin/
# phpMyAdmin refuses to start when its config file is world writable, which is what a build on
# Windows (or with a strange umask) would produce. Nothing in there is writable for others.
RUN chmod 644 public/phpmyadmin/config.inc.php public/phpmyadmin/signon.php && chmod -R go-w public/phpmyadmin
# A throwaway key lets composer's artisan hooks run cleanly during the build; the .env with it is
# deleted right after, and the real key is created on first start (see entrypoint.sh).
RUN cp .env.example .env \
    && sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(head -c 32 /dev/urandom | base64)|" .env \
    && mkdir -p bootstrap/cache/ storage/logs storage/framework/sessions storage/framework/views storage/framework/cache \
    && chmod 777 -R bootstrap storage \
    && composer install --no-dev --optimize-autoloader \
    && rm -rf .env bootstrap/cache/*.php \
    && mkdir -p /app/storage/logs/ \
    && find . -path ./public/phpmyadmin -prune -o -exec chown nginx:nginx {} +

# Which commit this image was built from; shown in Settings -> Updates and used to detect new versions.
ARG MC_COMMIT=unknown
RUN echo "$MC_COMMIT" > /app/.mc-commit \
    && date -u +%Y-%m-%dT%H:%M:%SZ > /app/.mc-built-at

EXPOSE 80 443
ENTRYPOINT [ "/bin/ash", ".github/docker/entrypoint.sh" ]
CMD [ "supervisord", "-n", "-c", "/etc/supervisord.conf" ]
