<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie\Watchlist;

use Doctrine\DBAL\Connection;
use Movary\Domain\Movie\Watchlist\MovieWatchlistRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieWatchlistRepository::class)]
class MovieWatchlistRepositoryTest extends TestCase
{
    public function testSearchesLocalizedAndOriginalTitles() : void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchFirstColumn')
            ->with(
                self::callback(
                    static fn(string $query) : bool => str_contains(
                        $query,
                        '(m.title LIKE ? OR m.original_title LIKE ?)',
                    ),
                ),
                [42, '%Chihiro%', '%Chihiro%'],
            )
            ->willReturn([1]);

        self::assertSame(
            1,
            (new MovieWatchlistRepository($connection))->fetchWatchlistCount(
                userId: 42,
                searchTerm: 'Chihiro',
                releaseYear: null,
                language: null,
                genre: null,
            ),
        );
    }
}
