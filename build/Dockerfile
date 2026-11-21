FROM composer:2.11.1@sha256:8de2184c6de224f4689e8f80ed5f5edaf0030b6b8b7839a22a2bcb6722ac87d0 AS composer

FROM php:8.6.0-fpm-alpine3.24@sha256:3892978a38794b348f73d6926fb63e352b5338f8b302cdd2cf0ed968f63b50c5 AS base

LABEL Description="Movary is a self hosted web app to track and rate your watched movies"
LABEL Maintainer="Lee Peuker"

ARG USER_ID=3000
ARG GROUP_ID=3000

RUN apk upgrade --no-cache \
    && apk add --no-cache \
    bash \
    curl \
    libzip \
    oniguruma \
    nginx \
    supervisor \
    && apk add --no-cache --virtual .php-extension-build-dependencies \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install mbstring pdo_mysql zip \
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

ARG APPLICATION_VERSION=unknown
ARG APPLICATION_REVISION=unknown

RUN printf '%s\n' "$APPLICATION_VERSION" > /app/VERSION \
    && printf '%s\n' "$APPLICATION_REVISION" > /app/REVISION

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

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --chown=application:application ./ /app

RUN composer install --no-interaction --no-progress --prefer-dist
