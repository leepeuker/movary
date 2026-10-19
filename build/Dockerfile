FROM composer:2.10.3@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac AS composer

FROM alpine:3.23@sha256:85fe1e81d6758c208f3e1eed4338a1997e19d4be002d4dd32d3100c9a8c010a0 AS base

LABEL Description="Movary is a self hosted web app to track and rate your watched movies"
LABEL Maintainer="Lee Peuker"

ARG USER_ID=3000
ARG GROUP_ID=3000

ENV PHP_INI_DIR=/etc/php85

RUN apk upgrade --no-cache \
    && apk add --no-cache \
    bash \
    curl \
    nginx \
    php85 \
    php85-ctype \
    php85-curl \
    php85-dom \
    php85-fileinfo \
    php85-fpm \
    php85-gd \
    php85-intl \
    php85-mbstring \
    php85-mysqli \
    php85-openssl \
    php85-pdo \
    php85-pdo_mysql \
    php85-pdo_sqlite \
    php85-phar \
    php85-session \
    php85-simplexml \
    php85-sqlite3 \
    php85-tokenizer \
    php85-xml \
    php85-xmlreader \
    php85-xmlwriter \
    php85-zip \
    php85-pecl-imagick \
    supervisor

# Create standard binary name without duplicating the PHP executable
RUN ln -s /usr/bin/php85 /usr/bin/php

# Create entrypoint script (for automatic database migrations)
COPY ./build/scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create stop_supervisor_on_process_failure script (stop supervisor to kill container if a processes fails)
COPY ./build/scripts/stop_supervisor_on_process_failure.sh /usr/local/bin/stop_supervisor_on_process_failure.sh
RUN chmod +x /usr/local/bin/stop_supervisor_on_process_failure.sh

# Create runtime configuration
COPY ./build/config/conf.d /etc/nginx/conf.d
COPY ./build/config/nginx.conf /etc/nginx/nginx.conf
COPY ./build/config/fpm-pool.conf $PHP_INI_DIR/php-fpm.d/www.conf
COPY ./build/config/php.ini $PHP_INI_DIR/conf.d/custom.ini
COPY ./build/config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create non root user
RUN addgroup -g "$GROUP_ID" -S application \
    && adduser -u "$USER_ID" -S application -G application \
    && mkdir -p /app \
    && chown application:application /app /run /var/lib/nginx /var/log/nginx

# Switch to non root (improve security)
USER application

# Set app directory as working directory
WORKDIR /app

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping || exit 1

FROM base AS production-dependencies

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --chown=application:application ./composer.json ./composer.lock /app/

RUN composer install \
    --no-autoloader \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist

COPY --chown=application:application ./db/migrations /app/db/migrations
COPY --chown=application:application ./src /app/src

RUN composer dump-autoload --no-dev --classmap-authoritative

FROM base AS production

ARG APPLICATION_VERSION
ENV APPLICATION_VERSION=${APPLICATION_VERSION}

COPY --chown=application:application ./bootstrap.php /app/bootstrap.php
COPY --chown=application:application ./bin /app/bin
COPY --chown=application:application ./db/migrations /app/db/migrations
COPY --chown=application:application ./docs/openapi.json /app/docs/openapi.json
COPY --chown=application:application ./public /app/public
COPY --chown=application:application ./settings/phinx.php ./settings/routes.php /app/settings/
COPY --chown=application:application ./src /app/src
COPY --chown=application:application ./storage /app/storage
COPY --chown=application:application ./templates /app/templates
COPY --from=production-dependencies --chown=application:application /app/vendor /app/vendor

FROM base AS development

USER root

RUN apk add --no-cache php85-pecl-xdebug

ARG XDEBUG_INI="$PHP_INI_DIR/conf.d/xdebug.ini"
RUN echo 'zend_extension=xdebug.so' > "$XDEBUG_INI" \
    && echo "[xdebug]" >> "$XDEBUG_INI" \
    && echo "xdebug.mode=\${XDEBUG_MODE:-off}" >> "$XDEBUG_INI" \
    && echo "xdebug.discover_client_host=true" >> "$XDEBUG_INI" \
    && echo "xdebug.start_with_request=yes" >> "$XDEBUG_INI" \
    && echo "xdebug.trigger_value=yes" >> "$XDEBUG_INI" \
    && echo "xdebug.log_level=0" >> "$XDEBUG_INI" \
    && echo "xdebug.idekey=docker" >> "$XDEBUG_INI" \
    && echo "xdebug.client_host=\${XDEBUG_HOST:-host.docker.internal}" >> "$XDEBUG_INI" \
    && echo "xdebug.client_port=\${XDEBUG_PORT:-9003}" >> "$XDEBUG_INI"

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --chown=application:application ./ /app

USER application

RUN composer install --no-interaction --no-progress --prefer-dist
