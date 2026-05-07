FROM serversideup/php:8.5-frankenphp AS dev

USER root

RUN install-php-extensions intl

USER www-data

FROM node:lts-alpine AS node

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY *.config.ts ./
COPY resources/ resources/

RUN npm run build

FROM dev AS production

USER root

COPY composer.json composer.lock ./
RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --no-dev \
    --optimize-autoloader \
    && composer clear-cache

COPY . .

COPY --from=node /app/public/build public/build

RUN composer dump-autoload --optimize

RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache \
    && php artisan filament:optimize

RUN chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache \
    && chown www-data:www-data /var/www/html/public

USER www-data
