<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use Movary\DatabaseMigration\Version20260928000000;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversNothing]
class BaselineMigrationTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20260928000000.php';
    }

    public function testCreatesCanonicalSqliteSchema() : void
    {
        $connection = $this->createMigratedConnection();

        try {
            $tableNames = $connection->createSchemaManager()->listTableNames();

            self::assertCount(26, $tableNames);
            self::assertContains('user_password_reset_token', $tableNames);
        } finally {
            $connection->close();
        }
    }

    public function testDefinesEveryApplicationTable() : void
    {
        $tableNames = array_map(
            static fn ($table) : string => $table->getName(),
            Version20260928000000::createCutoverSchema()->getTables(),
        );
        sort($tableNames);

        self::assertSame([
            'cache_letterboxd_diary',
            'cache_tmdb_languages',
            'cache_trakt_user_movie_rating',
            'cache_trakt_user_movie_watched',
            'company',
            'country',
            'genre',
            'job_queue',
            'location',
            'movie',
            'movie_cast',
            'movie_crew',
            'movie_genre',
            'movie_production_company',
            'movie_production_countries',
            'movie_user_rating',
            'movie_user_watch_dates',
            'person',
            'server_setting',
            'user',
            'user_api_token',
            'user_auth_token',
            'user_jellyfin_cache',
            'user_password_reset_token',
            'user_person_settings',
            'watchlist',
        ], $tableNames);
    }

    public function testAppliesConfiguredDefaultTableOptions() : void
    {
        $schemaConfig = new SchemaConfig();
        $schemaConfig->setDefaultTableOptions([
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'engine' => 'InnoDB',
        ]);

        $schema = Version20260928000000::createCutoverSchema($schemaConfig);

        foreach ($schema->getTables() as $table) {
            self::assertSame('utf8mb4', $table->getOption('charset'));
            self::assertSame('utf8mb4_unicode_ci', $table->getOption('collation'));
            self::assertSame('InnoDB', $table->getOption('engine'));
        }
    }

    public function testDefinesCutoverKeysAndDefaults() : void
    {
        $schema = Version20260928000000::createCutoverSchema();

        self::assertSame(
            ['user_id', 'trakt_id'],
            $schema->getTable('cache_trakt_user_movie_rating')->getPrimaryKey()?->getColumns(),
        );
        self::assertSame(
            ['user_id', 'trakt_id'],
            $schema->getTable('cache_trakt_user_movie_watched')->getPrimaryKey()?->getColumns(),
        );
        self::assertSame(
            ['movie_id', 'user_id'],
            $schema->getTable('movie_user_rating')->getPrimaryKey()?->getColumns(),
        );
        self::assertSame(
            'public',
            $schema->getTable('user')->getColumn('mastodon_post_visibility')->getDefault(),
        );
        self::assertFalse($schema->getTable('person')->getColumn('tmdb_id')->getNotnull());
    }

    public function testRejectsUnsupportedPersonGenderOnSqlite() : void
    {
        $connection = $this->createMigratedConnection();

        try {
            $this->expectException(Exception::class);
            $connection->insert('person', [
                'name' => 'Invalid gender',
                'gender' => 9,
                'created_at' => '2026-01-01 00:00:00',
            ]);
        } finally {
            $connection->close();
        }
    }

    public function testRejectsUnsupportedMastodonVisibilityOnSqlite() : void
    {
        $connection = $this->createMigratedConnection();

        try {
            $this->expectException(Exception::class);
            $connection->insert('user', [
                'email' => 'user@example.test',
                'name' => 'user',
                'password' => 'secret',
                'mastodon_post_visibility' => 'followers',
                'created_at' => '2026-01-01 00:00:00',
            ]);
        } finally {
            $connection->close();
        }
    }

    public function testIsNonTransactionalAndIrreversible() : void
    {
        $connection = DriverManager::getConnection([
            'driver' => 'sqlite3',
            'memory' => true,
        ]);
        $migration = new Version20260928000000($connection, new NullLogger());

        try {
            self::assertFalse($migration->isTransactional());
            $this->expectException(IrreversibleMigration::class);

            $migration->down(new Schema());
        } finally {
            $connection->close();
        }
    }

    private function createMigratedConnection() : Connection
    {
        $connection = DriverManager::getConnection([
            'driver' => 'sqlite3',
            'memory' => true,
        ]);
        $connection->executeStatement('PRAGMA foreign_keys = ON');

        $migration = new Version20260928000000($connection, new NullLogger());
        $migration->up(new Schema());

        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement());
        }

        return $connection;
    }
}
