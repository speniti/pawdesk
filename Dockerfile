FROM serversideup/php:8.5-frankenphp

USER root

RUN install-php-extensions intl bcmath

USER www-data
