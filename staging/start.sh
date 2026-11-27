#!/bin/sh
set -eu
cd "$(dirname "$0")/.."

: "${TANOD_STAGING_PASSWORD:?A test login password must be set}"
: "${APP_URL:=${RENDER_EXTERNAL_URL:-http://127.0.0.1:8142}}"
export APP_URL ASSET_URL="$APP_URL"
export APP_ENV=staging APP_NAME="Tanod Staging" APP_DEBUG=false
export APP_KEY="${APP_KEY:-$(php -r 'echo "base64:".base64_encode(random_bytes(32));')}"
export TANOD_STAGING_FLEET_MODE=unconfigured
if [ "${TANOD_STAGING_USE_SAMPLE_DATA:-0}" = '1' ]; then
    export TANOD_STAGING_FLEET_MODE=sample
    export JIMI_BASE_URL=http://127.0.0.1:18143 JIMI_USER_ID=tanod-staging JIMI_APP_KEY=staging JIMI_API_SECRET=staging JIMI_USER_PWD_MD5=staging
elif [ -n "${JIMI_APP_KEY:-}" ] && [ -n "${JIMI_API_SECRET:-}" ] &&
     [ -n "${JIMI_USER_ID:-}" ] && [ -n "${JIMI_USER_PWD_MD5:-}" ]; then
    export TANOD_STAGING_FLEET_MODE=tracksolid
fi
export DB_CONNECTION=sqlite DB_DATABASE="$PWD/storage/app/tanod-staging-${TANOD_STAGING_FLEET_MODE}.sqlite"
export SESSION_DRIVER=file SESSION_COOKIE=tanod_staging_session CACHE_STORE=file
export QUEUE_CONNECTION=sync BROADCAST_CONNECTION=null MAIL_MAILER=array
export JIMI_MAP_TYPE=GOOGLE
case "$APP_URL" in
    https://*) export SESSION_SECURE_COOKIE=true ;;
    *) export SESSION_SECURE_COOKIE=false ;;
esac

mkdir -p storage/app storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch "$DB_DATABASE"
php artisan migrate --force --no-interaction
php staging/seed.php

if [ "$TANOD_STAGING_FLEET_MODE" = 'sample' ]; then
    php -S 127.0.0.1:18143 staging/jimi.php > storage/logs/staging-jimi.log 2>&1 &
    jimi_process=$!
fi

if [ "$TANOD_STAGING_FLEET_MODE" != 'unconfigured' ]; then
    (
        while :; do
            php staging/sync-service-area.php || true
            sleep 300
        done
    ) &
    service_area_process=$!
fi

if [ "$TANOD_STAGING_FLEET_MODE" = 'sample' ]; then
    trap 'kill "$jimi_process" "$service_area_process" 2>/dev/null || true' EXIT HUP INT TERM
    php -S "0.0.0.0:${PORT:-8142}" -t public staging/server.php
else
    exec php -S "0.0.0.0:${PORT:-8142}" -t public staging/server.php
fi
