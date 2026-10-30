<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie;

use Doctrine\DBAL\DriverManager;
use Movary\Domain\Movie\MovieRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieRepository::class)]
class MovieRepositoryPlayCountTest extends TestCase
{
    public function testPlayTotalsCountRowsEvenIfLegacyPlaysColumnDiffers() : void
    {
        $connection = DriverManager::getConnection(['driver' => 'sqlite3', 'memory' => true]);
        $connection->executeStatement('CREATE TABLE movie_user_watch_dates (movie_id INTEGER, user_id INTEGER, plays INTEGER)');
        $connection->executeStatement('INSERT INTO movie_user_watch_dates (movie_id, user_id, plays) VALUES (10, 7, 9), (10, 7, 1), (11, 7, 1), (10, 8, 1)');
        $repository = new MovieRepository($connection);

        self::assertSame(3, $repository->fetchTotalPlayCount(7));
        self::assertSame(2, $repository->fetchTotalPlaysForMovieAndUserId(10, 7));
        self::assertSame(0, $repository->fetchTotalPlaysForMovieAndUserId(12, 7));
    }
}
