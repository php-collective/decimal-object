ARG PHP_VERSION=8.5

FROM php:${PHP_VERSION}-cli-alpine
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN docker-php-ext-install bcmath && docker-php-ext-enable bcmath

