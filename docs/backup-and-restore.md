# Backup and restore

A complete Movary backup contains:

- the database;
- the complete `storage` directory;
- the configuration, such as `.env`, environment variables, and secrets; and
- the Movary version used when the backup was created.

Settings entered in the web UI are stored in the database. Environment variables and secrets are not, so the database alone is not enough to recreate an installation.

!!! warning

    Backups contain user data and can contain credentials. Store them securely and test the restore procedure regularly.

## Create a backup

Stop Movary and its background worker before creating a backup. This prevents the database or files from changing during the backup.

### SQLite

The default SQLite database is `storage/movary.sqlite`. Back up the complete `storage` directory rather than only this file.

If `DATABASE_SQLITE` points outside `storage`, back up that database file separately. Do not copy a SQLite database while Movary is running because an active transaction can result in an inconsistent backup.

### MySQL

Create a logical dump of the database configured by `DATABASE_MYSQL_*`. For example:

```shell
mysqldump \
  --host=MYSQL_HOST \
  --port=3306 \
  --user=MYSQL_USER \
  --password \
  --single-transaction \
  --quick \
  --skip-lock-tables \
  --no-tablespaces \
  MYSQL_DATABASE > movary-database.sql
```

Also back up the complete `storage` directory. Do not copy the files of a running MySQL database directly.

## Restore a backup

1. Install the same Movary version that created the backup.
2. Restore the saved configuration and secrets.
3. Restore the complete `storage` directory and its original permissions.
4. For MySQL, import the database dump into an empty database.
5. Recreate the public storage link:

    ```shell
    php bin/console.php storage:link
    ```

6. Check the database migration status:

    ```shell
    php bin/console.php database:migration:status
    ```

7. Start Movary and its background worker.

After confirming that the restored installation works, follow the normal update procedure if a newer Movary version is required.

## Verify the restore

Sign in and check users, watch history, ratings, watchlists, and server settings. Also confirm that the background worker starts without errors.

See the official documentation for [SQLite backup safety](https://www.sqlite.org/howtocorrupt.html#_backup_or_restore_while_a_transaction_is_active) and [`mysqldump`](https://dev.mysql.com/doc/refman/8.0/en/mysqldump.html) for more advanced backup requirements.
