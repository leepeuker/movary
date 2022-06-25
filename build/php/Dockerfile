# https://hub.docker.com/_/php
FROM webdevops/php-nginx:8.1-alpine

ENV WEB_DOCUMENT_ROOT="/app/public"

USER application
WORKDIR /app
COPY --chown=application ./ ./
