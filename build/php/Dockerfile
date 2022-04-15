# https://hub.docker.com/_/php
FROM php:8.1.4-alpine3.15

# System update
RUN apk update && apk upgrade

# Install system dependencies
RUN apk add --no-cache git                  \
                       bash                 \
                       nano                 \
                       curl                 \
                       unzip                \
                       make                 \
                       zip                  \
                       libzip-dev           \
                       $PHPIZE_DEPS


# Install php dependencies
RUN docker-php-ext-install mysqli pdo pdo_mysql zip

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=2.3.3

WORKDIR /app
