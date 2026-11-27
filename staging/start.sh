#!/bin/sh
set -eu
cd "$(dirname "$0")/.."

: "${TANOD_STAGING_PASSWORD:?A test login password must be set}"
: "${APP_URL:=${RENDER_EXTERNAL_URL:-http://127.0.0.1:8142}}"
export APP_URL ASSET_URL="$APP_URL"
export APP_ENV=staging APP_NAME="Tanod Staging" APP_DEBUG=false
export APP_KEY="${APP_KEY:-$(php -r 'echo "base64:".base64_encode(random_bytes(32));')}"
export DB_CONNECTION=sqlite DB_DATABASE="$PWD/storage/app/tanod-staging.sqlite"
export SESSION_DRIVER=file SESSION_COOKIE=tanod_staging_session CACHE_STORE=file
export QUEUE_CONNECTION=sync BROADCAST_CONNECTION=null MAIL_MAILER=array
export JIMI_BASE_URL=http://127.0.0.1:18143 JIMI_USER_ID=tanod-staging JIMI_APP_KEY=staging JIMI_API_SECRET=staging
export JIMI_MAP_TYPE=GOOGLE
case "$APP_URL" in
    https://*) export SESSION_SECURE_COOKIE=true ;;
    *) export SESSION_SECURE_COOKIE=false ;;
esac

mkdir -p storage/app storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch "$DB_DATABASE"
php artisan migrate --force --no-interaction
php staging/seed.php

php -S 127.0.0.1:18143 staging/jimi.php > storage/logs/staging-jimi.log 2>&1 &
jimi_process=$!
trap 'kill "$jimi_process" 2>/dev/null || true' EXIT HUP INT TERM
php -S "0.0.0.0:${PORT:-8142}" -t public staging/server.php
