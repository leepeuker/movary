FROM openswoole/swoole:php8.3 as base

COPY . /app
WORKDIR /app

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENTRYPOINT ["/bin/bash", "/app/build/entrypoint.sh"]

FROM base AS dev

RUN composer install

FROM base AS prod

RUN composer install --no-dev
