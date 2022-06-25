FROM webdevops/php-nginx:8.1-alpine  AS development
ENV WEB_DOCUMENT_ROOT="/app/public"
USER application
WORKDIR /app

FROM base AS production
COPY --chown=application ./ ./
COPY .env.production.example .env
RUN composer install --no-dev
