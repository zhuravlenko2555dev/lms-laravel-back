#!/bin/sh
# Container entrypoint for all three roles (web / worker / scheduler).
# Responsibilities: prepare writable dirs and rebuild per-release framework
# caches, then exec the role command. It NEVER runs database migrations —
# migrations are a guarded Helm pre-upgrade Job in the config repo.
set -eu

cd /var/www/html

# These paths are emptyDir mounts when readOnlyRootFilesystem is enabled, so
# they start empty and must be (re)created before artisan writes to them.
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache

# Rebuild caches for THIS release. Config values come from the container
# environment (Kubernetes env + mounted secrets), so this must run at boot,
# not at build time.
php artisan package:discover --ansi
php artisan config:cache
php artisan event:cache
php artisan view:cache
# NOTE: `route:cache` is intentionally omitted. This app defines closure-based
# routes (routes/web.php catch-all and /up), which Laravel cannot serialize.
# To enable route caching, convert those routes to invokable controllers.

# Hand off to the role command (php-fpm / queue:work / schedule:work).
exec "$@"
