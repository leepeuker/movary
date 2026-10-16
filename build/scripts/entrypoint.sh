#!/bin/bash

# Allow the file-based secret to be used when Compose provides an empty direct value.
if [ -z "${APPLICATION_SECRET:-}" ] && [ -n "${APPLICATION_SECRET_FILE:-}" ]; then
  unset APPLICATION_SECRET
fi

CONFIG_VALIDATION_ERROR=$(
  /usr/bin/php -r '
    try {
        $container = require "/app/bootstrap.php";
        $container->get(\Movary\Service\ApplicationSecret::class);
    } catch (\Throwable $throwable) {
        fwrite(STDERR, $throwable->getMessage());
        exit(1);
    }
  ' 2>&1
)

if [ $? -ne 0 ]; then
  echo "ALERT: $CONFIG_VALIDATION_ERROR"
  exit 1
fi

if [ "${DATABASE_DISABLE_AUTO_MIGRATION:-0}" != "true" ] && [ "${DATABASE_DISABLE_AUTO_MIGRATION:-0}" != "1" ]; then
  RETRY_COUNT=0
  MAX_RETRIES=5

  echo "INFO: Automatic database migration is enabled"

  while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    /usr/bin/php /app/bin/console.php database:migration:migrate

    if [ $? -eq 0 ]; then
      echo "SUCCESS: Automatic database migration succeeded"
      break
    else
      RETRY_COUNT=$((RETRY_COUNT + 1))
      echo "ERROR: Automatic database migration failed, attempt $RETRY_COUNT of $MAX_RETRIES"
      if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
        echo "ALERT: Automatic database migration failed after $MAX_RETRIES attempts, exiting..."
        exit 1
      fi
      echo "INFO: Retrying database migration in 5 seconds..."
      sleep 5
    fi
  done
else
  echo "INFO: Automatic database migration is disabled";
fi

echo "INFO: Generating symbolic link for storage"
/usr/bin/php /app/bin/console.php storage:link

exec "$@"
