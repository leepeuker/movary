FROM composer:2.10.3@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac AS composer

FROM php:8.5.12-fpm-alpine3.24@sha256:4c9541a4506014672d074652a1cc9754d3c5f97cad6c6027bae35be0e4a4dea9 AS base

LABEL Description="Movary is a self hosted web app to track and rate your watched movies"
LABEL Maintainer="Lee Peuker"

ARG USER_ID=3000
ARG GROUP_ID=3000

RUN apk upgrade --no-cache \
    && apk add --no-cache \
    bash \
    curl \
    libzip \
    nginx \
    supervisor \
    && apk add --no-cache --virtual .php-extension-build-dependencies \
    libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && apk del .php-extension-build-dependencies

# Keep the PHP path used by the entrypoint and Supervisor configuration.
RUN ln -s /usr/local/bin/php /usr/bin/php

# Create entrypoint script (for automatic database migrations)
COPY ./build/scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create stop_supervisor_on_process_failure script (stop supervisor to kill container if a processes fails)
COPY ./build/scripts/stop_supervisor_on_process_failure.sh /usr/local/bin/stop_supervisor_on_process_failure.sh
RUN chmod +x /usr/local/bin/stop_supervisor_on_process_failure.sh

# Create runtime configuration
COPY ./build/config/conf.d /etc/nginx/conf.d
COPY ./build/config/nginx.conf /etc/nginx/nginx.conf
COPY ./build/config/fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf
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

RUN apk add --no-cache \
    --virtual .xdebug-build-dependencies \
    $PHPIZE_DEPS \
    linux-headers \
    && pecl install xdebug-3.5.3 \
    && apk del .xdebug-build-dependencies

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
