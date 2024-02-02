#!/bin/sh

php php bin/console.php storage:link
php php bin/console.php database:migration:migrate

php /app/public/index.php
