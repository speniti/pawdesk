# Tag floating pinnati a digest (#74): un rebuild produce lo stesso contenuto
# e un tag compromesso non può entrare nella build. Il digest è quello
# dell'index multi-arch; gli aggiornamenti li propone Dependabot (ecosistema
# docker). Digest risolti il 2026-11-26 via registry API.
FROM serversideup/php:8.5-frankenphp@sha256:ca0a1ab19a5c661f48ff63d75029cec26f07b2d9cc5f543f79e9a75cca931c61 AS dev

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

# 24-alpine invece di lts-alpine: major esplicito, allineato alla CI
# (actions/setup-node usa node-version: 24).
FROM node:24-alpine@sha256:116b800c3754b440ee0aa3ed2f20f5bf3d99a554bfd9adeea09ea8017f6e85f5 AS node

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
    && chown www-data:www-data /var/www/html/database \
    && chown www-data:www-data /var/www/html/public

USER www-data
