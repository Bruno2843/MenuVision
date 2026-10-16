FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libsqlite3-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev curl nodejs npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
&& docker-php-ext-install pdo pdo_sqlite mbstring zip gd \
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
    && printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' \
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
RUN printf "upload_max_filesize=64M\npost_max_size=80M\nmax_execution_time=300\nmax_input_time=300\n" \
    > /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 80

CMD ["/usr/local/bin/docker-entrypoint.sh"]

