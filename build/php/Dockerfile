FROM webdevops/php-nginx:8.1-alpine AS development
ENV WEB_DOCUMENT_ROOT="/app/public"
RUN apk add --no-cache make nano
USER application
WORKDIR /app

FROM development AS production
ARG APPLICATION_VERSION
ENV APPLICATION_VERSION=${APPLICATION_VERSION}
COPY --chown=application ./ ./
COPY .env.production.example .env
RUN composer install --no-dev
