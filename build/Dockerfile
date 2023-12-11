FROM php:8.3-alpine as base

# Install dev dependencies
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS curl-dev imagemagick-dev \
    libtool libxml2-dev postgresql-dev sqlite-dev

# Install production dependencies
RUN apk add --no-cache bash curl freetype-dev g++ gcc git icu-dev icu-libs imagemagick  \
    libc-dev libjpeg-turbo-dev libpng-dev libzip-dev make mysql-client oniguruma-dev postgresql-libs supervisor zlib-dev

# Install PECL and PEAR extensions
RUN pecl install igbinary openswoole

# Enable PECL and PEAR extensions
RUN docker-php-ext-enable igbinary openswoole

# Install php extensions
RUN docker-php-ext-install bcmath curl mbstring pdo pdo_mysql pdo_sqlite xml zip

# Cleanup dev dependencies
RUN apk del -f .build-deps

# Install composer
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV PATH $PATH:/root/.composer/vendor/bin
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Supervisor
COPY ./build/supervisord.conf /etc/supervisord.conf
COPY ./build/supervisor.d /etc/supervisor.d

# Setup working directory
WORKDIR /app

# Port Expose
EXPOSE 9501

# Entrypoint
COPY ./build/entrypoint.sh /docker-entrypoint.sh

ENTRYPOINT ["sh", "/docker-entrypoint.sh"]

FROM base as development
COPY . /app

RUN composer install
RUN php bin/console.php storage:link
