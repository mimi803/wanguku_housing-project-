FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm install --no-audit --no-fund && npm run build

FROM php:8.2-apache
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libzip-dev libonig-dev \
    && docker-php-ext-install mbstring pdo_pgsql zip \
    && a2enmod rewrite headers \
    && sed -ri -e 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && sed -ri 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -ri 's/\*:80>/\*:10000>/' /etc/apache2/sites-available/*.conf \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --no-dev --optimize \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APACHE_DOCUMENT_ROOT=/var/www/public
COPY --from=assets /app/public/build ./public/build
EXPOSE 10000

CMD ["sh", "-c", "case \"$APP_KEY\" in base64:*) ;; *) export APP_KEY=base64:$APP_KEY ;; esac && for attempt in $(seq 1 20); do php artisan migrate --force && break; [ \"$attempt\" -eq 20 ] && exit 1; sleep 3; done && php artisan storage:link && exec apache2-foreground"]
