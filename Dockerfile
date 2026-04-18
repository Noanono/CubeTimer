FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    nodejs \
    npm \
    git \
    unzip \
    libzip-dev \
    icu-data \
    sqlite-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite zip intl opcache \
    && npm install -g npm@latest

COPY --link package.json package-lock.json* ./

RUN npm ci --if-present && npm run build

COPY --link . .

RUN cp .env.example .env \
    && php artisan key:generate \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data .

USER www-data

EXPOSE 9000

CMD ["php-fpm"]