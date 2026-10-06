FROM php:8.3-fpm-alpine AS system-setup

RUN apk --no-cache add \
    curl \
    git \
    linux-headers \
    icu-dev \
    zip \
    libzip-dev \
    unzip \
    nginx \
    supervisor \
    $PHPIZE_DEPS && \
    pecl install apcu

RUN curl --remote-name https://curl.se/ca/cacert.pem \
    && mv cacert.pem /etc/ssl/certs/ca-certificates.crt

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    intl \
    zip \
    opcache && \
    docker-php-ext-enable apcu

COPY --from=composer:2.8.9 /usr/bin/composer /usr/local/bin/composer

COPY ./docker/main/default.conf /etc/nginx/http.d/default.conf
COPY ./docker/main/supervisord.conf /etc/supervisord.conf
COPY ./docker/main/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./docker/main/local.ini /usr/local/etc/php/conf.d/local.ini

# FPM configuration via ENV
ENV FPM_PM=dynamic
ENV FPM_PM_MAX_CHILDREN=5
ENV FPM_PM_START_SERVERS=2
ENV FPM_PM_MIN_SPARE_SERVERS=1
ENV FPM_PM_MAX_SPARE_SERVERS=3

# PHP configuration via ENV
ENV LOG_TO_STDOUT=1
ENV PHP_MEMORY_LIMIT=512M
ENV PHP_MAX_EXECUTION_TIME=30
ENV PHP_UPLOAD_MAX_FILESIZE=120M
ENV PHP_POST_MAX_SIZE=128M
ENV PHP_SESSION_SAVE_HANDLER=files
ENV PHP_SESSION_SAVE_PATH=
ENV PHP_DISPLAY_ERRORS=0
ENV PHP_DISPLAY_STARTUP_ERRORS=0

FROM node:24-alpine AS assets

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build

FROM system-setup AS app-setup

COPY . /app
COPY --from=assets /app/public/build /app/public/build

RUN chown -R nginx:nginx /app

COPY ./docker/main/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER nginx
WORKDIR /app

RUN composer install --prefer-dist --no-scripts --no-dev --optimize-autoloader

EXPOSE 80

HEALTHCHECK --interval=15s --timeout=5s --retries=3 \
 CMD curl -s -o /dev/null -f http://localhost/v4/ping || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
