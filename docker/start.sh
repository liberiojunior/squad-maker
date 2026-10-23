#!/usr/bin/env sh
set -eu

export PORT="${PORT:-10000}"

: "${APP_KEY:?APP_KEY não configurada}"
: "${REVERB_APP_ID:?REVERB_APP_ID não configurado}"
: "${REVERB_APP_KEY:?REVERB_APP_KEY não configurada}"
: "${REVERB_APP_SECRET:?REVERB_APP_SECRET não configurado}"

envsubst '$PORT' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

php artisan package:discover --ansi
php artisan config:cache --ansi
php artisan view:cache --ansi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
