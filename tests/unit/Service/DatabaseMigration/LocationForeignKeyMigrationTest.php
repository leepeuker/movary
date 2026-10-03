<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Movary\DatabaseMigration\Version20260928000000;
use Movary\DatabaseMigration\Version20261003190000;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversNothing]
class LocationForeignKeyMigrationTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20260928000000.php';
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20261003190000.php';
    }

    public function testDeletingLocationPreservesWatchDate() : void
    {
        $connection = $this->createMigratedConnection();

        try {
            $this->insertWatchDate($connection);

            $connection->delete('location', ['id' => 3]);

            self::assertSame(1, $connection->fetchOne('SELECT COUNT(*) FROM movie_user_watch_dates'));
            $watchDate = $connection->fetchAssociative(
                'SELECT watched_at, plays, comment, position, location_id FROM movie_user_watch_dates',
            );
            self::assertIsArray($watchDate);
            self::assertSame(
                ['2026-10-01', 2, 'Cinema visit', 4, null],
                array_values($watchDate),
            );
        } finally {
            $connection->close();
        }
    }

    public function testRollbackRestoresCascadeDelete() : void
    {
        $connection = $this->createMigratedConnection();

        try {
            $this->executeMigration(
                new Version20261003190000($connection, new NullLogger()),
                $connection,
                false,
            );
            $this->insertWatchDate($connection);

            $connection->delete('location', ['id' => 3]);

            self::assertSame(0, $connection->fetchOne('SELECT COUNT(*) FROM movie_user_watch_dates'));
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

        $this->executeMigration(new Version20260928000000($connection, new NullLogger()), $connection);
        $this->executeMigration(new Version20261003190000($connection, new NullLogger()), $connection);

        return $connection;
    }

    private function executeMigration(
        AbstractMigration $migration,
        Connection $connection,
        bool $up = true,
    ) : void {
        if ($up === true) {
            $migration->up(new Schema());
        } else {
            $migration->down(new Schema());
        }

        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement());
        }
    }

    private function insertWatchDate(Connection $connection) : void
    {
        $connection->insert('user', [
            'id' => 1,
            'email' => 'user@example.test',
            'name' => 'user',
            'password' => 'password',
            'created_at' => '2026-10-01',
        ]);
        $connection->insert('movie', [
            'id' => 2,
            'title' => 'Movie',
            'tmdb_id' => 123,
            'created_at' => '2026-10-01',
        ]);
        $connection->insert('location', [
            'id' => 3,
            'user_id' => 1,
            'name' => 'Cinema',
            'created_at' => '2026-10-01',
        ]);
        $connection->insert('movie_user_watch_dates', [
            'movie_id' => 2,
            'user_id' => 1,
            'watched_at' => '2026-10-01',
            'plays' => 2,
            'comment' => 'Cinema visit',
            'position' => 4,
            'location_id' => 3,
        ]);
    }
}
