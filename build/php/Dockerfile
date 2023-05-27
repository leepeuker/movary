FROM webdevops/php-nginx:8.2-alpine AS development
ENV WEB_DOCUMENT_ROOT="/app/public" \
    PHP_DISMOD=imagick
RUN apk add --no-cache make nano
USER application
WORKDIR /app

FROM development AS production
ARG APPLICATION_VERSION
ENV APPLICATION_VERSION=${APPLICATION_VERSION}
COPY --chown=application ./ ./
COPY .env.production.example .env
COPY settings/supervisor/movary.conf /opt/docker/etc/supervisor.d/movary.conf
RUN composer install --no-dev
RUN php bin/console.php storage:link
