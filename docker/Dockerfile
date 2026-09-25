# syntax=docker/dockerfile:1.7
#
# Multi-stage, hardened image for the Laravel API + Vue 3 SPA.
# ONE image, THREE roles (web / queue-worker / scheduler) selected by the
# command in Kubernetes. See docs/USAGE.md and docs/SECURITY-REVIEW.md.
#
# NOTE on pinning: bases are pinned by tag here. For production you MUST also
# pin by digest (tag@sha256:...). Resolve the current digest with:
#   docker buildx imagetools inspect php:8.3-fpm-alpine
# and append it to each FROM line. This is called out in SECURITY-REVIEW.md.

##############################################################################
# php-base — PHP 8.3 FPM (Alpine) with the required extensions.
# Shared by the vendor build stage and the final runtime so the extension set
# is byte-for-byte identical between build and run.
##############################################################################
FROM php:8.3-fpm-alpine AS php-base

WORKDIR /var/www/html

# Runtime shared libraries + fcgi (used by the HEALTHCHECK client). The build
# toolchain is installed as a virtual package and removed afterwards so no
# compilers or headers remain in the final image.
RUN apk add --no-cache \
        fcgi \
        freetype \
        icu-libs \
        libjpeg-turbo \
        libpng \
        libzip \
 && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del .build-deps \
 && rm -rf /tmp/pear /usr/local/lib/php/doc/*

# PHP / OPcache / php-fpm pool tuning. www.conf OVERWRITES the image default
# pool (defining a second [www] pool would fail to start).
COPY docker/php/php.ini     /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/php/www.conf    /usr/local/etc/php-fpm.d/www.conf

##############################################################################
# frontend — build the Vue 3 SPA assets with Vite into public/build.
# This repo uses laravel-vite-plugin, so Vite emits a hashed bundle + manifest
# into public/build (there is no standalone dist/).
##############################################################################
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js tsconfig.json postcss.config.js components.d.ts ./
COPY resources ./resources
COPY public ./public
RUN npm run build

##############################################################################
# vendor — install production PHP dependencies.
##############################################################################
FROM php-base AS vendor
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install deps without scripts/autoloader first for better layer caching.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --no-progress \
        --prefer-dist

# Add the application source, then build the optimized, authoritative autoloader
# and run package discovery (writes bootstrap/cache/*.php).
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
 && composer run-script post-autoload-dump --no-dev 2>/dev/null || true

##############################################################################
# runtime — minimal image. Default command is php-fpm (web role). Kubernetes
# overrides the command for the queue-worker and scheduler roles.
##############################################################################
FROM php-base AS runtime

# Application source (dockerignore keeps out node_modules/vendor/.env/.git),
# then overlay the built vendor tree, package caches, and SPA assets.
COPY --chown=82:82 . .
COPY --from=vendor   --chown=82:82 /var/www/html/vendor          ./vendor
COPY --from=vendor   --chown=82:82 /var/www/html/bootstrap/cache ./bootstrap/cache
COPY --from=frontend --chown=82:82 /app/public/build            ./public/build

# nginx sidecar config is shipped inside the image so an initContainer can copy
# it (together with public/) into a volume shared with the stock nginx sidecar.
# See docs/USAGE.md → "nginx sidecar contract".
COPY docker/nginx /var/www/nginx

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
 # Drop things not needed at runtime.
 && rm -rf tests .github docker/php docker/entrypoint.sh \
 # Pre-create the writable dirs that become emptyDir mounts under a
 # read-only root filesystem, owned by the non-root user (uid 82 = www-data).
 && mkdir -p \
        storage/framework/views \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/logs \
        storage/app/public \
        bootstrap/cache \
 && chown -R 82:82 storage bootstrap/cache public/build

# Non-root (Alpine www-data). Matches runAsNonRoot / runAsUser: 82 in the chart.
USER 82:82

EXPOSE 9000

# Advisory HEALTHCHECK for the WEB role (pings php-fpm). Worker/scheduler roles
# have no fpm listener; Kubernetes probes are the authoritative liveness signal
# and override this per role.
HEALTHCHECK --interval=30s --timeout=3s --start-period=30s --retries=3 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET \
        cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm", "-F"]
