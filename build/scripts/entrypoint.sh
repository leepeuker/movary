#!/bin/bash

if [ "$DATABASE_DISABLE_AUTO_MIGRATION" != "true" ] && [ "$DATABASE_DISABLE_AUTO_MIGRATION" != "0" ]; then
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
      fi
      echo "INFO: Retrying database migration in 5 seconds..."
      sleep 5
    fi
  done
else
  echo "INFO: Automatic database migration is disabled";
fi

exec "$@"
