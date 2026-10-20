#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs bootstrap/cache

# SQLite is only the local engine. Production runs on PostgreSQL, so creating
# the file there would just leave an unused database behind (and, worse, make
# the app look like it is using SQLite when it is not).
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    mkdir -p database
    [ -f database/database.sqlite ] || touch database/database.sqlite
fi

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
if [ -d database ]; then
    chown -R www-data:www-data database || true
    chmod -R 775 database || true
fi

# The symlink only matters for the local disk. With MENUVISION_DISK=supabase
# the menu is served straight from Storage, so a failure here is harmless.
php artisan storage:link || true

php artisan config:clear

# Migrations are opt-in on purpose: a deploy should not reshape the production
# database implicitly. Run them once from the Render shell after verifying the
# connection, then keep this flag off.
if [ "${RUN_MIGRATIONS_ON_BOOT:-false}" = "true" ]; then
    php artisan migrate --force
fi

# Users are never seeded here. UserSeeder resets the passwords of the demo
# accounts, and running it on every boot would silently undo a real password.
exec apache2-foreground
