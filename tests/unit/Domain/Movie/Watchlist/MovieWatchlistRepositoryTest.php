<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie\Watchlist;

use Doctrine\DBAL\Connection;
use Movary\Domain\Movie\Watchlist\MovieWatchlistRepository;
use Movary\ValueObject\MovieTitleDisplay;
use Movary\ValueObject\SortOrder;
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

    public function testSortsByViewerTitlePreference() : void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                self::callback(
                    static fn(string $query) : bool => str_contains(
                        $query,
                        "ORDER BY LOWER(COALESCE(NULLIF(m.original_title, ''), m.title)) asc",
                    ) && str_contains($query, 'SELECT movie_title_display FROM `user`') === false,
                ),
                [42, 42, '%%', '%%'],
            )
            ->willReturn([]);

        self::assertSame(
            [],
            (new MovieWatchlistRepository($connection))->fetchWatchlistPaginated(
                userId: 42,
                limit: 24,
                page: 1,
                searchTerm: null,
                sortBy: 'title',
                sortOrder: SortOrder::createAsc(),
                releaseYear: null,
                language: null,
                genre: null,
                productionCountryCode: null,
                movieTitleDisplay: MovieTitleDisplay::createFromString('original'),
            ),
        );
    }
}
