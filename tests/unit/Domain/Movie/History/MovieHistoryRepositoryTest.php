<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie\History;

use Doctrine\DBAL\DriverManager;
use Movary\Domain\Movie\History\MovieHistoryRepository;
use Movary\ValueObject\Date;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieHistoryRepository::class)]
class MovieHistoryRepositoryTest extends TestCase
{
    public function testHighestPositionIncludesOtherPlaysOfSameMovie() : void
    {
        $connection = DriverManager::getConnection(['driver' => 'sqlite3', 'memory' => true]);
        $connection->executeStatement('CREATE TABLE movie_user_watch_dates (movie_id INTEGER, user_id INTEGER, watched_at TEXT, position INTEGER)');
        $connection->insert('movie_user_watch_dates', ['movie_id' => 11, 'user_id' => 22, 'watched_at' => '2026-10-03', 'position' => 2]);
        $connection->insert('movie_user_watch_dates', ['movie_id' => 11, 'user_id' => 22, 'watched_at' => '2026-10-03', 'position' => 3]);
        $connection->insert('movie_user_watch_dates', ['movie_id' => 12, 'user_id' => 23, 'watched_at' => '2026-10-03', 'position' => 9]);
        $repository = new MovieHistoryRepository($connection);

        self::assertSame(3, $repository->fetchHighestPositionForWatchDate(22, Date::createFromString('2026-10-03')));
    }
}
