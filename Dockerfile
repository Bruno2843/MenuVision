FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libsqlite3-dev libpq-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev curl nodejs npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_sqlite pdo_pgsql mbstring zip gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm ci --legacy-peer-deps \
    && npm run build

RUN sed -i 's#/var/www/html#/var/www/html/public#g' \
    /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted</Directory>\n' \
    > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# A 3D model may be up to 100 MB (see StoreDishRequest). PHP has to accept it
# before validation runs, and the upload is read into memory, so both the
# request size and the memory limit are above that ceiling.
RUN printf "upload_max_filesize=128M\npost_max_size=140M\nmemory_limit=512M\nmax_execution_time=600\nmax_input_time=600\nmax_file_uploads=20\n" \
    > /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 80

CMD ["/usr/local/bin/docker-entrypoint.sh"]
