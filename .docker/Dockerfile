ARG PHP_VERSION=8.2
ARG PHPUNIT_VERSION=10.4.0
ARG PHPSTAN_VERSION=1.10.38

FROM php:${PHP_VERSION}-cli-alpine
COPY --from=composer /usr/bin/composer /usr/bin/composer

ARG PHPUNIT_VERSION
ARG PHPSTAN_VERSION

RUN docker-php-ext-install bcmath && docker-php-ext-enable bcmath
RUN wget -O phpunit https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar && chmod +x phpunit
RUN wget -O phpstan https://github.com/phpstan/phpstan/releases/download/${PHPSTAN_VERSION}/phpstan.phar && chmod +x phpstan

