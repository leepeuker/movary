<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\DatabaseMigration;

use Doctrine\DBAL\DriverManager;
use Movary\Service\DatabaseMigration\CanonicalSchemaProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CanonicalSchemaProvider::class)]
class CanonicalSchemaProviderTest extends TestCase
{
    private CanonicalSchemaProvider $provider;

    protected function setUp() : void
    {
        $this->provider = new CanonicalSchemaProvider();
    }

    public function testDefinesEveryApplicationTable() : void
    {
        $tableNames = array_map(
            static fn ($table) : string => $table->getName(),
            $this->provider->createSchema()->getTables(),
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

    public function testDefinesCutoverKeysAndDefaults() : void
    {
        $schema = $this->provider->createSchema();

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

    public function testGeneratedSchemaCanBeCreatedOnSqlite() : void
    {
        $connection = DriverManager::getConnection([
            'driver' => 'sqlite3',
            'memory' => true,
        ]);

        try {
            foreach ($this->provider->createSchema()->toSql($connection->getDatabasePlatform()) as $sql) {
                $connection->executeStatement($sql);
            }

            $tableNames = $connection->createSchemaManager()->listTableNames();

            self::assertCount(26, $tableNames);
            self::assertContains('user_password_reset_token', $tableNames);
        } finally {
            $connection->close();
        }
    }
}
