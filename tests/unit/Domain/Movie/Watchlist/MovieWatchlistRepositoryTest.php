<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie\Watchlist;

use Doctrine\DBAL\Connection;
use Movary\Domain\Movie\Watchlist\MovieWatchlistRepository;
use Movary\ValueObject\SortOrder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieWatchlistRepository::class)]
class MovieWatchlistRepositoryTest extends TestCase
{
    public function testFetchWatchlistPaginatedIncludesLastWatchedDate() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                self::callback(
                    static fn(string $query) : bool => str_contains(
                        $query,
                        'SELECT MAX(muwd.watched_at)',
                    ) && str_contains($query, 'as lastWatchedAt'),
                ),
                [42, 42, 42, '%%'],
            )
            ->willReturn([['lastWatchedAt' => '2026-10-05 20:00:00']]);

        $subject = new MovieWatchlistRepository($connection);

        self::assertSame(
            [['lastWatchedAt' => '2026-10-05 20:00:00']],
            $subject->fetchWatchlistPaginated(
                userId: 42,
                limit: 24,
                page: 1,
                searchTerm: null,
                sortBy: 'addedAt',
                sortOrder: SortOrder::createDesc(),
                releaseYear: null,
                language: null,
                genre: null,
                productionCountryCode: null,
            ),
        );
    }
}
