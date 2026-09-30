# Database migrations

Movary uses the standalone [Doctrine Migrations](https://www.doctrine-project.org/projects/doctrine-migrations/en/3.9/index.html)
package with the existing Doctrine DBAL connection. New migrations are shared
by MySQL and SQLite and live in `db/migrations/doctrine`.

Generate a migration with:

```shell
php bin/console.php database:migration:generate
```

The generated class is a starting point. Review its SQL and make sure the
migration preserves existing data and works on both supported database
platforms. Platform-specific SQL is allowed only when DBAL cannot express the
same logical operation portably, and both branches must be tested.

Run migrations and inspect their status through Movary's stable commands:

```shell
php bin/console.php database:migration:migrate
php bin/console.php database:migration:status
php bin/console.php database:migration:rollback
```

The baseline migration is intentionally irreversible because rolling it back
would delete the complete application schema.

## Legacy bridge

The old Phinx migrations remain temporarily so installations from an older
Movary release can reach the cutover boundary. Do not add new Phinx migrations.

During an upgrade, `database:migration:migrate`:

1. finishes remaining legacy Phinx migrations;
2. validates tables, columns, keys, indexes, foreign keys, defaults, and value
   constraints against the canonical schema;
3. records the Doctrine baseline without recreating validated legacy tables;
4. runs pending Doctrine migrations.

An empty database executes the Doctrine baseline directly. A legacy history
with gaps or unknown versions, or a schema with unknown drift, fails before
Doctrine metadata is written.
