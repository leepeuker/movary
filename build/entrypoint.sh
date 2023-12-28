#!/bin/sh

php /app/bin/console.php database:migration:migrate
/usr/bin/supervisord -n -c /etc/supervisord.conf
