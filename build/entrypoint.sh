#!/bin/sh

/usr/bin/crontab /var/www/crontab
/usr/bin/supervisord -n -c /etc/supervisord.conf
