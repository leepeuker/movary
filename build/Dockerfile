FROM composer:latest AS composer
FROM trafex/php-nginx AS production

USER root

RUN apk add --no-cache \
    make \
    nano \
    php84-pdo \
    php84-pdo_mysql \
    php84-mbstring \
    php84-sqlite3 \
    php84-simplexml \
    php84-pecl-imagick

RUN cp /usr/bin/php84 /usr/bin/php

COPY ./build/config/conf.d /etc/nginx/conf.d
COPY ./build/config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY --chown=nobody ./ /var/www/html

COPY .env.production.example .env

USER nobody

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev

RUN /usr/bin/php /var/www/html/bin/console.php storage:link

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

FROM production AS development

USER root

RUN apk add --no-cache make nano php84-pecl-imagick

RUN adduser -D -u 1000 --ingroup www-data www-data
RUN chown -R www-data:www-data /var/www/html /run /var/lib/nginx /var/log/nginx

USER www-data

RUN composer install

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
