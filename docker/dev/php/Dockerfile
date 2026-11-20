FROM php:8.3-fpm-alpine3.24 AS base

RUN apk --no-cache add \
    curl \
    git \
    linux-headers \
    icu-dev \
    zip \
    libzip-dev \
    unzip

RUN docker-php-ext-install \
    pdo \
    pdo_mysql

RUN docker-php-ext-install \
    intl \
    zip

RUN docker-php-ext-install opcache

RUN apk --no-cache add $PHPIZE_DEPS
RUN pecl install apcu \
    && docker-php-ext-enable apcu

RUN curl --remote-name https://curl.se/ca/cacert.pem \
    && mv cacert.pem /etc/ssl/certs/ca-certificates.crt

COPY --from=composer:lts /usr/bin/composer /usr/local/bin/composer

FROM base AS dev

RUN apk --no-cache add $PHPIZE_DEPS
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY . /app
RUN chown -R www-data:www-data /app

USER www-data
WORKDIR /app

RUN composer install --prefer-dist --no-scripts

FROM base AS prod

COPY . /app
RUN chown -R www-data:www-data /app

COPY /docker/dev/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER www-data
WORKDIR /app

RUN composer install --prefer-dist --no-scripts --no-dev --optimize-autoloader

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["php-fpm"]
