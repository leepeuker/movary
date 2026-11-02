<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie;

use Doctrine\DBAL\Connection;
use Movary\Domain\Movie\MovieRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieRepository::class)]
class MovieRepositoryPersonMoviesTest extends TestCase
{
    /** @param non-empty-string $method */
    #[DataProvider('personMovieQueries')]
    public function testLatestWatchDateIsScopedToSelectedUser(string $method, array $parameters) : void
    {
        $movies = [
            ['id' => 1, 'title' => 'Alpha', 'lastWatchedAt' => '2026-01-01', 'userRating' => 8],
            ['id' => 2, 'title' => 'Beta', 'lastWatchedAt' => null, 'userRating' => null],
        ];
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('fetchAllAssociative')->with(
            self::callback(static function (string $sql) : bool {
                self::assertStringContainsString(
                    '(SELECT MAX(history.watched_at) FROM movie_user_watch_dates history WHERE history.movie_id = m.id AND history.user_id = ?) AS lastWatchedAt',
                    $sql,
                );

                return true;
            }),
            $parameters,
        )->willReturn($movies);
        $repository = new MovieRepository($connection);

        self::assertSame($movies, $repository->$method(12, 34));
    }

    public static function personMovieQueries() : array
    {
        return [
            'watched actor' => ['fetchWithActor', [34, 34, 12, 34]],
            'watched director' => ['fetchWithDirector', [34, 34, 34, 12]],
            'watchlist actor' => ['fetchFromWatchlistWithActor', [34, 34, 12, 34]],
            'watchlist director' => ['fetchFromWatchlistWithDirector', [34, 34, 34, 12]],
        ];
    }
}
