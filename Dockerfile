FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    sqlite-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite zip intl opcache

# Les dépendances npm sont installées localement via setup-production.sh
# On copy seulement le code source (sans node_modules qui est déjà Buildé)
COPY --link package.json package-lock.json* ./
COPY --link resources resources/
COPY --link app app/
COPY --link bootstrap bootstrap/
COPY --link config config/
COPY --link public public/
COPY --link routes routes/
COPY --link storage storage/
COPY --link vendor vendor/
COPY --link artisan ./
COPY --link .env.example ./

RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data .

USER www-data

EXPOSE 9000

CMD ["php-fpm"]