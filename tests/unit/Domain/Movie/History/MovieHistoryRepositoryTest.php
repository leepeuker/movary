<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie\History;

use Doctrine\DBAL\Connection;
use Movary\Domain\Movie\History\MovieHistoryRepository;
use Movary\ValueObject\Date;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieHistoryRepository::class)]
class MovieHistoryRepositoryTest extends TestCase
{
    public function testHighestPositionIncludesOtherPlaysOfSameMovie() : void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('fetchFirstColumn')->with(
            'SELECT MAX(position)
            FROM movie_user_watch_dates
            WHERE watched_at = ? AND user_id = ?',
            ['2026-10-03', 22],
        )->willReturn([3]);
        $repository = new MovieHistoryRepository($connection);

        self::assertSame(3, $repository->fetchHighestPositionForWatchDate(22, Date::createFromString('2026-10-03')));
    }
}
