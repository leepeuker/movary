ARG ALPINE_VERSION=3.18
FROM alpine:${ALPINE_VERSION} as base
LABEL Maintainer="Tim de Pater <code@trafex.nl>"
LABEL Description="Lightweight container with Nginx 1.24 & PHP 8.2 based on Alpine Linux."
# Setup document root
WORKDIR /app

# Install packages and remove default server definition
RUN apk add --no-cache \
  curl \
  nano \
  nginx \
  php82 \
  php82-ctype \
  php82-curl \
  php82-dom \
  php82-fileinfo \
  php82-fpm \
  php82-gd \
  php82-intl \
  php82-json \
  php82-mbstring \
  php82-mysqli \
  php82-opcache \
  php82-openssl \
  php82-pdo_mysql \
  php82-phar \
  php82-session \
  php82-simplexml \
  php82-tokenizer \
  php82-xml \
  php82-xmlreader \
  php82-xmlwriter \
  supervisor

# build/configure nginx - http
COPY build/config/nginx.conf /etc/nginx/nginx.conf
# build/configure nginx - default server
COPY build/config/conf.d /etc/nginx/conf.d/

# build/configure PHP-FPM
ENV PHP_INI_DIR /etc/php82
COPY build/config/fpm-pool.conf ${PHP_INI_DIR}/php-fpm.d/www.conf
COPY build/config/php.ini ${PHP_INI_DIR}/conf.d/custom.ini

# build/configure supervisord
COPY build/config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Make sure files/folders needed by the processes are accessable when they run under the nobody user
RUN chown -R nobody.nobody /app /run /var/lib/nginx /var/log/nginx

# Create symlink for php
RUN ln -s /usr/bin/php82 /usr/bin/php

# Install composer from the official image
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Switch to use a non-root user from here on
USER nobody

# Expose the port nginx is reachable on
EXPOSE 8080

# Let supervisord start nginx & php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# configure a healthcheck to validate that everything is up&running
HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping

FROM base as development

#FROM base as production
#COPY --chown=nobody ./ /var/www/html/
