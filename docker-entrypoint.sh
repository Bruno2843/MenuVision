
#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs bootstrap/cache

touch database/database.sqlite

chown -R www-data:www-data storage bootstrap/cache database/database.sqlite

php artisan config:clear
php artisan migrate --force

exec apache2-foreground
