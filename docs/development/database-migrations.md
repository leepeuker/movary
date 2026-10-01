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

Movary `0.74.0` is the first release using Doctrine Migrations. The old Phinx
migration histories remain available for several releases so installations on
older releases can catch up through the normal upgrade process. Do not add new
Phinx migrations.

The frozen legacy histories end at different backend-specific versions:

- MySQL: `20260927233000`
- SQLite: `20260927221000`

These version numbers are part of the bridge preflight. The migration command
accepts only an exact prefix of the checked-in history and does not guess how to
handle missing or unknown Phinx versions.

During an upgrade, `database:migration:migrate`:

1. finishes remaining legacy Phinx migrations;
2. validates tables, columns, keys, indexes, foreign keys, defaults, and value
   constraints against the canonical schema;
3. records the Doctrine baseline without recreating validated legacy tables;
4. runs pending Doctrine migrations.

An empty database executes the Doctrine baseline directly. A legacy history
with gaps or unknown versions, or a schema with unknown drift, fails before
Doctrine metadata is written.

## Removing the legacy bridge

Phinx is scheduled for removal in Movary `1.0.0`. Installations that have not
reached the Doctrine baseline must upgrade to the final `0.x` release and run
its migrations before upgrading to `1.0.0`. Keep the complete frozen Phinx
histories and the bridge code in every release until then.

For the `1.0.0` removal:

1. retain a lightweight check for `phinxlog` without the Doctrine baseline and
   fail with an actionable message directing the user through the final `0.x`
   release;
2. remove the Phinx application wiring, configuration, dependency, and legacy
   MySQL and SQLite migration directories;
3. simplify the public migration commands and migration state handling to use
   Doctrine only;
4. add a Doctrine migration that removes the obsolete `phinxlog` table from
   databases that have already crossed the baseline;
5. replace the bridge-specific tests with coverage for a final `0.x` upgrade,
   a fresh `1.0.0` installation, an existing Doctrine-managed installation,
   and rejection of a database that skipped the bridge release;
6. update the installation and release documentation with the mandatory
   pre-`1.0.0` upgrade step and run the complete test suite on both MySQL and
   SQLite.
