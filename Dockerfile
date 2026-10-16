
FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libsqlite3-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev curl \
    && docker-php-ext-install pdo pdo_sqlite mbstring zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . /var/www/html

WORKDIR /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN apt-get update && apt-get install -y nodejs npm \
    && npm ci && npm run build \
    && rm -rf /var/lib/apt/lists/*

RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/html\/public/' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

RUN mkdir -p storage/framework/cache storage/framework/sessions \
    storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
