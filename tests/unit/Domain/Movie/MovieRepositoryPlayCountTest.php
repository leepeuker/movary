<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie;

use Doctrine\DBAL\Connection;
use Movary\Domain\Movie\MovieRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieRepository::class)]
class MovieRepositoryPlayCountTest extends TestCase
{
    public function testTotalPlayCountCountsRows() : void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('fetchOne')
            ->with('SELECT COUNT(*) FROM movie_user_watch_dates WHERE user_id = ?', [7])
            ->willReturn('3');
        $repository = new MovieRepository($connection);

        self::assertSame(3, $repository->fetchTotalPlayCount(7));
    }

    public function testMoviePlayCountCountsRowsAndReturnsZeroWhenNoneMatch() : void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::exactly(2))->method('fetchOne')
            ->willReturnCallback(static function (string $sql, array $parameters) : string {
                self::assertSame('SELECT COUNT(*) FROM movie_user_watch_dates WHERE movie_id = ? AND user_id = ?', $sql);
                self::assertContains($parameters, [[10, 7], [12, 7]]);

                return $parameters[0] === 10 ? '2' : '0';
            });
        $repository = new MovieRepository($connection);

        self::assertSame(2, $repository->fetchTotalPlaysForMovieAndUserId(10, 7));
        self::assertSame(0, $repository->fetchTotalPlaysForMovieAndUserId(12, 7));
    }
}
