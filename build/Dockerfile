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

HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping || exit 1

FROM base AS production

ARG APPLICATION_VERSION
ENV APPLICATION_VERSION=${APPLICATION_VERSION}

RUN composer install --no-dev

FROM base AS development

USER root

RUN apk add --no-cache php84-pecl-xdebug

ARG XDEBUG_INI="$PHP_INI_DIR/conf.d/xdebug.ini"
RUN echo 'zend_extension=xdebug.so' > $XDEBUG_INI \
    && echo "[xdebug]" >> $XDEBUG_INI \
    && echo "xdebug.mode=\${XDEBUG_MODE:-off}" >> $XDEBUG_INI \
    && echo "xdebug.discover_client_host=true" >> $XDEBUG_INI \
    && echo "xdebug.start_with_request=yes" >> $XDEBUG_INI \
    && echo "xdebug.trigger_value=yes" >> $XDEBUG_INI \
    && echo "xdebug.log_level=0" >> $XDEBUG_INI \
    && echo "xdebug.idekey=docker" >> $XDEBUG_INI \
    && echo "xdebug.client_host=\${XDEBUG_HOST:-host.docker.internal}" >> $XDEBUG_INI \
    && echo "xdebug.client_port=\${XDEBUG_PORT:-9003}" >> $XDEBUG_INI

USER application

RUN composer install
