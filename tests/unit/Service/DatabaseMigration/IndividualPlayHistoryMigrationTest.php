<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Movary\DatabaseMigration\Version20261029000000;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

#[CoversClass(Version20261029000000::class)]
class IndividualPlayHistoryMigrationTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20261029000000.php';
    }

    public function testExpandsAggregateRowsAndKeepsMetadataOnFirstPlay() : void
    {
        $connection = $this->createConnection();
        $this->insertHistory($connection, 10, 2, '2026-01-01', 3, 'A comment', 1, 7);
        $this->insertHistory($connection, 11, 2, '2026-01-01', 1, null, 1, null);
        $this->insertHistory($connection, 10, 2, null, 2, null, 2, null);

        $migration = new Version20261029000000($connection, new NullLogger());
        $migration->up(new Schema());

        $rows = $connection->fetchAllAssociative('SELECT id, movie_id, watched_at, plays, comment, position, location_id FROM movie_user_watch_dates ORDER BY id');
        self::assertCount(6, $rows);
        self::assertSame(6, (int)$connection->fetchOne('SELECT SUM(plays) FROM movie_user_watch_dates'));
        self::assertSame([null, null, 'A comment', null, null, null], array_column($rows, 'comment'));
        self::assertSame([null, null, 7, null, null, null], array_map(static fn(array $row) : ?int => $row['location_id'] === null ? null : (int)$row['location_id'], $rows));
        self::assertSame([10, 10, 10, 10, 10, 11], array_map(static fn(array $row) : int => (int)$row['movie_id'], $rows));
        self::assertFalse($connection->fetchOne("SELECT name FROM sqlite_master WHERE name = 'movie_user_watch_dates_aggregate_backup'"));
    }

    #[DataProvider('invalidSourceValues')]
    public function testRejectsInvalidSourceBeforeChangingSchema(?int $plays, ?int $position) : void
    {
        $connection = $this->createConnection();
        $this->insertHistory($connection, 10, 2, '2026-01-01', $plays, null, $position, null);

        try {
            (new Version20261029000000($connection, new NullLogger()))->up(new Schema());
            self::fail('Expected invalid source rows to be rejected.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('invalid plays or positions', $exception->getMessage());
        }

        self::assertFalse($connection->fetchOne("SELECT name FROM sqlite_master WHERE name = 'movie_user_watch_dates_play_staging'"));
    }

    public static function invalidSourceValues() : array
    {
        return [
            'null plays' => [null, 1],
            'zero plays' => [0, 1],
            'negative plays' => [-1, 1],
            'oversized plays' => [32768, 1],
            'null position' => [1, null],
            'zero position' => [1, 0],
            'negative position' => [1, -1],
            'oversized position' => [1, 32768],
        ];
    }

    public function testExpandsAHistoryRowAcrossInsertBatches() : void
    {
        $connection = $this->createConnection();
        $this->insertHistory($connection, 10, 2, '2026-01-01', 101, 'Once', 1, 7);

        (new Version20261029000000($connection, new NullLogger()))->up(new Schema());

        self::assertSame(101, (int)$connection->fetchOne('SELECT COUNT(*) FROM movie_user_watch_dates'));
        self::assertSame(1, (int)$connection->fetchOne('SELECT COUNT(*) FROM movie_user_watch_dates WHERE comment IS NOT NULL AND location_id IS NOT NULL'));
    }

    public function testExpandsMaximumLegacySmallintPlayCount() : void
    {
        $connection = $this->createConnection();
        $this->insertHistory($connection, 10, 2, '2026-01-01', 32767, 'First only', 1, 7);

        (new Version20261029000000($connection, new NullLogger()))->up(new Schema());

        self::assertSame(32767, (int)$connection->fetchOne('SELECT COUNT(*) FROM movie_user_watch_dates'));
        self::assertSame(1, (int)$connection->fetchOne('SELECT COUNT(*) FROM movie_user_watch_dates WHERE comment IS NOT NULL'));
    }

    public function testRefusesToExpandAnAlreadyMigratedTable() : void
    {
        $connection = $this->createConnection();
        $migration = new Version20261029000000($connection, new NullLogger());
        $migration->up(new Schema());

        $this->expectExceptionMessage('History already has play IDs');
        (new Version20261029000000($connection, new NullLogger()))->up(new Schema());
    }

    public function testRejectsUndrainedPlayPostingJobs() : void
    {
        $connection = $this->createConnection();
        $connection->insert('job_queue', ['job_type' => 'mastodon_post_play', 'job_status' => 'waiting']);

        $this->expectExceptionMessage('Drain pending Mastodon play-posting jobs');
        (new Version20261029000000($connection, new NullLogger()))->up(new Schema());
    }

    public function testRejectsMoreThanOneMillionExpandedPlays() : void
    {
        $connection = $this->createConnection();
        for ($index = 0; $index < 31; $index++) {
            $this->insertHistory($connection, 10 + $index, 2, '2026-01-01', 32767, null, 1, null);
        }

        $this->expectExceptionMessage('1,000,000-play limit');
        (new Version20261029000000($connection, new NullLogger()))->up(new Schema());
    }

    private function createConnection() : Connection
    {
        $connection = DriverManager::getConnection(['driver' => 'sqlite3', 'memory' => true]);
        $connection->executeStatement('CREATE TABLE movie (id INTEGER PRIMARY KEY)');
        $connection->executeStatement('CREATE TABLE user (id INTEGER PRIMARY KEY)');
        $connection->executeStatement('CREATE TABLE location (id INTEGER PRIMARY KEY)');
        $connection->executeStatement('CREATE TABLE job_queue (job_type TEXT NOT NULL, job_status TEXT NOT NULL)');
        $connection->executeStatement('CREATE TABLE movie_user_watch_dates (movie_id INTEGER, user_id INTEGER, watched_at TEXT, plays INTEGER, comment TEXT, position INTEGER, location_id INTEGER)');
        $connection->executeStatement('INSERT INTO user (id) VALUES (2)');
        $connection->executeStatement('INSERT INTO location (id) VALUES (7)');
        for ($movieId = 10; $movieId <= 40; $movieId++) {
            $connection->insert('movie', ['id' => $movieId]);
        }

        return $connection;
    }

    private function insertHistory(
        Connection $connection,
        int $movieId,
        int $userId,
        ?string $watchedAt,
        ?int $plays,
        ?string $comment,
        ?int $position,
        ?int $locationId,
    ) : void {
        $connection->insert('movie_user_watch_dates', [
            'movie_id' => $movieId,
            'user_id' => $userId,
            'watched_at' => $watchedAt,
            'plays' => $plays,
            'comment' => $comment,
            'position' => $position,
            'location_id' => $locationId,
        ]);
    }
}
