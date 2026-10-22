<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Movary\DatabaseMigration\Version20260928000000;
use Movary\DatabaseMigration\Version20261022000000;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(Version20261022000000::class)]
class MoviePosterOverrideMigrationTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20260928000000.php';
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20261022000000.php';
    }

    public function testAddsNullablePosterOverrideWithoutChangingExistingPosterData() : void
    {
        $connection = $this->createBaselineConnection();
        $connection->insert('movie', [
            'title' => 'Movie',
            'tmdb_id' => 42,
            'poster_path' => '/cached.jpg',
            'tmdb_poster_path' => '/default.jpg',
            'created_at' => '2026-10-22 10:00:00',
        ]);

        try {
            $this->executeMigration($connection, new Version20261022000000($connection, new NullLogger()));
            $movie = $connection->fetchAssociative(
                'SELECT poster_path, tmdb_poster_path, tmdb_poster_path_override FROM movie WHERE tmdb_id = 42',
            );
            if ($movie === false) {
                self::fail('Could not find migrated movie.');
            }

            self::assertSame('/cached.jpg', $movie['poster_path']);
            self::assertSame('/default.jpg', $movie['tmdb_poster_path']);
            self::assertNull($movie['tmdb_poster_path_override']);
        } finally {
            $connection->close();
        }
    }

    public function testMigrationCanBeRolledBack() : void
    {
        $connection = $this->createBaselineConnection();

        try {
            $this->executeMigration($connection, new Version20261022000000($connection, new NullLogger()));
            $migration = new Version20261022000000($connection, new NullLogger());
            $migration->down(new Schema());
            $this->executeQueries($connection, $migration);

            self::assertArrayNotHasKey(
                'tmdb_poster_path_override',
                $connection->createSchemaManager()->listTableColumns('movie'),
            );
        } finally {
            $connection->close();
        }
    }

    private function createBaselineConnection() : Connection
    {
        $connection = DriverManager::getConnection(['driver' => 'sqlite3', 'memory' => true]);
        $this->executeMigration($connection, new Version20260928000000($connection, new NullLogger()));

        return $connection;
    }

    private function executeMigration(Connection $connection, AbstractMigration $migration) : void
    {
        $migration->up(new Schema());
        $this->executeQueries($connection, $migration);
    }

    private function executeQueries(Connection $connection, AbstractMigration $migration) : void
    {
        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }
}
