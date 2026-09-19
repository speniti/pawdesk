FROM serversideup/php:8.5-frankenphp AS dev

USER root

RUN install-php-extensions intl

USER www-data

FROM dev AS php-deps

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

FROM node:lts-alpine AS node

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY *.config.ts ./
COPY resources/ resources/
COPY app/ app/

# The Filament panel theme imports the base theme and scans views from vendor
COPY --from=php-deps /var/www/html/vendor/ vendor/

RUN npm run build

FROM dev AS production

USER root

COPY --from=php-deps /var/www/html/vendor/ vendor/

COPY . .

COPY --from=node /app/public/build public/build

RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache \
    && chown www-data:www-data /var/www/html/public

USER www-data
