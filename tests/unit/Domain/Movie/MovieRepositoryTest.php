<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Movary\Domain\Movie\MovieRepository;
use Movary\ValueObject\SortOrder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Domain\Movie\MovieRepository::class)]
class MovieRepositoryTest extends TestCase
{
    private MockObject|Connection $dbConnectionMock;

    private MovieRepository $subject;

    public function setUp() : void
    {
        $this->dbConnectionMock = $this->createMock(Connection::class);

        $this->subject = new MovieRepository($this->dbConnectionMock);
    }

    public static function providedTestFetchMovieIdsHavingImdbIdOrderedByLastImdbUpdatedAtData() : array
    {
        return [
            'mysqlDefault' => [
                <<<SQL
                SELECT movie.id
                FROM `movie` 
                WHERE movie.imdb_id IS NOT NULL AND (updated_at_imdb IS NULL OR updated_at_imdb <= datetime("now","-0 hours")) 
                ORDER BY updated_at_imdb ASC 
                SQL,
                true,
                null,
                null,
            ],
            'mysqlWithHours' => [
                <<<SQL
                SELECT movie.id
                FROM `movie` 
                WHERE movie.imdb_id IS NOT NULL AND (updated_at_imdb IS NULL OR updated_at_imdb <= datetime("now","-8 hours")) 
                ORDER BY updated_at_imdb ASC 
                SQL,
                true,
                8,
                null,
            ],
            'mysqlWitLimit' => [
                <<<SQL
                SELECT movie.id
                FROM `movie` 
                WHERE movie.imdb_id IS NOT NULL AND (updated_at_imdb IS NULL OR updated_at_imdb <= datetime("now","-0 hours")) 
                ORDER BY updated_at_imdb ASC LIMIT 50
                SQL,
                true,
                null,
                50,
            ],
            'sqliteDefault' => [
                <<<SQL
                SELECT movie.id
                FROM `movie` 
                WHERE movie.imdb_id IS NOT NULL AND (updated_at_imdb IS NULL OR updated_at_imdb <= DATE_SUB(NOW(), INTERVAL 0 HOUR)) 
                ORDER BY updated_at_imdb ASC 
                SQL,
                false,
                null,
                null,
            ],
            'sqliteWithHours' => [
                <<<SQL
                SELECT movie.id
                FROM `movie` 
                WHERE movie.imdb_id IS NOT NULL AND (updated_at_imdb IS NULL OR updated_at_imdb <= DATE_SUB(NOW(), INTERVAL 8 HOUR)) 
                ORDER BY updated_at_imdb ASC 
                SQL,
                false,
                8,
                null,
            ],
            'sqliteWithLimit' => [
                <<<SQL
                SELECT movie.id
                FROM `movie` 
                WHERE movie.imdb_id IS NOT NULL AND (updated_at_imdb IS NULL OR updated_at_imdb <= DATE_SUB(NOW(), INTERVAL 0 HOUR)) 
                ORDER BY updated_at_imdb ASC LIMIT 50
                SQL,
                false,
                null,
                50,
            ]
        ];
    }

    #[DataProvider('providedTestFetchMovieIdsHavingImdbIdOrderedByLastImdbUpdatedAtData')]
    public function testFetchMovieIdsHavingImdbIdOrderedByLastImdbUpdatedAt(string $expectedQuery, bool $isSqlite, ?int $maxAgeInHours, ?int $limit) : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('getDatabasePlatform')
            ->willReturn($isSqlite === true ? new SqlitePlatform() : new MySQLPlatform());

        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchFirstColumn')
            ->with($expectedQuery)
            ->willReturn(['result']);

        self::assertSame(
            $this->subject->fetchMovieIdsHavingImdbIdOrderedByLastImdbUpdatedAt($maxAgeInHours, $limit),
            ['result'],
        );
    }

    public function testFetchAllOrderedByLastUpdatedAtTmdbAscCanFilterNeverSyncedMoviesBeforeLimit() : void
    {
        $result = $this->createMock(Result::class);
        $result->expects(self::once())->method('iterateAssociative')->willReturn(new \ArrayIterator());

        $statement = $this->createMock(Statement::class);
        $statement->expects(self::once())->method('executeQuery')->with([7, 8])->willReturn($result);

        $this->dbConnectionMock
            ->expects(self::once())
            ->method('prepare')
            ->with(
                'SELECT * FROM `movie` WHERE updated_at_tmdb IS NULL AND id IN (?, ?) '
                . 'ORDER BY updated_at_tmdb, created_at LIMIT 50',
            )
            ->willReturn($statement);

        iterator_to_array($this->subject->fetchAllOrderedByLastUpdatedAtTmdbAsc(50, [7, 8], true));
    }

    public function testFetchPersonalRatingDistribution() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                'SELECT mur.rating, COUNT(DISTINCT mur.movie_id) as count
            FROM movie_user_rating mur
            JOIN movie_user_watch_dates muwd ON muwd.movie_id = mur.movie_id AND muwd.user_id = mur.user_id
            WHERE mur.user_id = ?
            GROUP BY mur.rating
            ORDER BY mur.rating',
                [42],
            )
            ->willReturn([['rating' => '7', 'count' => '3']]);

        self::assertSame(
            [['rating' => 7, 'count' => 3]],
            $this->subject->fetchPersonalRatingDistribution(42),
        );
    }

    public function testFetchPersonListsIncludeBirthDate() : void
    {
        $this->dbConnectionMock
            ->expects(self::exactly(2))
            ->method('fetchAllAssociative')
            ->with(
                self::callback(
                    static fn(string $query) : bool => str_contains($query, 'p.gender, p.birth_date,'),
                ),
                [42, '%%'],
            )
            ->willReturn([]);

        self::assertSame(
            [],
            $this->subject->fetchActors(
                userId: 42,
                limit: 24,
                page: 1,
                searchTerm: null,
                sortBy: 'name',
                sortOrder: SortOrder::createAsc(),
                gender: null,
                personFilterUserId: null,
            ),
        );
        self::assertSame(
            [],
            $this->subject->fetchDirectors(
                userId: 42,
                limit: 24,
                page: 1,
                searchTerm: null,
                sortBy: 'name',
                sortOrder: SortOrder::createAsc(),
                gender: null,
                personFilterUserId: null,
            ),
        );
    }

    public static function provideExternalRatingSortData() : array
    {
        return [
            'IMDb rating ascending' => ['imdbRating', SortOrder::createAsc(), 'imdb_rating_average asc'],
            'TMDB rating descending' => ['tmdbRating', SortOrder::createDesc(), 'tmdb_vote_average desc'],
        ];
    }

    #[DataProvider('provideExternalRatingSortData')]
    public function testFetchUniqueWatchedMoviesPaginatedCanSortByExternalRating(
        string $sortBy,
        SortOrder $sortOrder,
        string $expectedOrderBy,
    ) : void {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                self::callback(static fn(string $query) : bool => str_contains($query, "ORDER BY $expectedOrderBy,")),
                [42, 42, '%%'],
            )
            ->willReturn([]);

        self::assertSame(
            [],
            $this->subject->fetchUniqueWatchedMoviesPaginated(
                userId: 42,
                limit: 24,
                page: 1,
                searchTerm: null,
                sortBy: $sortBy,
                sortOrder: $sortOrder,
                releaseYear: null,
                language: null,
                genre: null,
                hasUserRating: null,
                userRatingMin: null,
                userRatingMax: null,
                locationId: null,
                productionCountryCode: null,
            ),
        );
    }

    public function testFetchUniqueWatchedMoviesPaginatedIncludesLastWatchedDate() : void
    {
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                self::callback(
                    static fn(string $query) : bool => str_contains(
                        $query,
                        'MAX(mh.watched_at) OVER(PARTITION BY m.id) as lastWatchedAt',
                    ),
                ),
                [42, 42, '%%'],
            )
            ->willReturn([['lastWatchedAt' => '2026-10-05 20:00:00']]);

        self::assertSame(
            [['lastWatchedAt' => '2026-10-05 20:00:00']],
            $this->subject->fetchUniqueWatchedMoviesPaginated(
                userId: 42,
                limit: 24,
                page: 1,
                searchTerm: null,
                sortBy: 'title',
                sortOrder: SortOrder::createAsc(),
                releaseYear: null,
                language: null,
                genre: null,
                hasUserRating: null,
                userRatingMin: null,
                userRatingMax: null,
                locationId: null,
                productionCountryCode: null,
            ),
        );
    }
}
