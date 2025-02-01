FROM composer:latest AS composer

FROM trafex/php-nginx AS base

LABEL Description="Movary is a self hosted web app to track and rate your watched movies"
LABEL Maintainer="Lee Peuker"

ARG USER_ID=3000
ARG GROUP_ID=3000

USER root

RUN apk add --no-cache \
    bash \
    php84-pdo \
    php84-pdo_mysql \
    php84-pdo_sqlite \
    php84-mbstring \
    php84-sqlite3 \
    php84-simplexml \
    php84-pecl-imagick

# Create standard binary for php
RUN cp /usr/bin/php84 /usr/bin/php

# Create entrypoint script (for automatic database migrations)
COPY ./build/scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create stop_supervisor_on_process_failure script (stop supervisor to kill container if a processes fails)
COPY ./build/scripts/stop_supervisor_on_process_failure.sh /usr/local/bin/stop_supervisor_on_process_failure.sh
RUN chmod +x /usr/local/bin/stop_supervisor_on_process_failure.sh

# Create configuration and appllication files
COPY ./build/config/conf.d /etc/nginx/conf.d
COPY ./build/config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./ /app

# Create non root user
RUN addgroup -g $USER_ID -S application && adduser -u $USER_ID -S application -G application
RUN chown -R application:application /app /run /var/lib/nginx /var/log/nginx

# Add composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Switch to non root (improve security)
USER application

# Set app directory as working directory
WORKDIR /app

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1/fpm-ping || exit 1

FROM base AS production

ARG APPLICATION_VERSION
ENV APPLICATION_VERSION=${APPLICATION_VERSION}

RUN composer install --no-dev

FROM base AS development

RUN composer install
