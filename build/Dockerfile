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

USER nobody

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

FROM production AS development

USER root

RUN apk add --no-cache make nano php84-pecl-imagick

RUN adduser -D -u 1000 --ingroup www-data www-data
RUN chown -R www-data:www-data /var/www/html /run /var/lib/nginx /var/log/nginx

USER www-data

RUN composer update

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]


#FROM development AS production
#ARG APPLICATION_VERSION
#ENV APPLICATION_VERSION=${APPLICATION_VERSION}
#COPY --chown=nobody ./ ./
#COPY .env.production.example .env
#COPY settings/supervisor/movary.conf /opt/docker/etc/supervisor.d/movary.conf
#RUN composer install --no-dev
#RUN php bin/console.php storage:link
