<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie;

use Doctrine\DBAL\DriverManager;
use Movary\Domain\Movie\MovieRepository;
use Movary\ValueObject\MovieTitleDisplay;
use Movary\ValueObject\SortOrder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieRepository::class)]
class MovieRepositoryWatchedRatingsTest extends TestCase
{
    private MovieRepository $subject;

    public function setUp() : void
    {
        $connection = DriverManager::getConnection(['driver' => 'sqlite3', 'memory' => true]);
        $connection->executeStatement('CREATE TABLE movie (id INTEGER, title TEXT, original_title TEXT, release_date TEXT)');
        $connection->executeStatement('CREATE TABLE movie_user_watch_dates (id INTEGER, movie_id INTEGER, user_id INTEGER, watched_at TEXT, position INTEGER)');
        $connection->executeStatement('CREATE TABLE movie_user_rating (movie_id INTEGER, user_id INTEGER, rating INTEGER)');
        $connection->executeStatement('CREATE TABLE movie_genre (movie_id INTEGER, genre_id INTEGER)');
        $connection->executeStatement('CREATE TABLE genre (id INTEGER, name TEXT)');
        $connection->executeStatement("INSERT INTO movie (id, title) VALUES (1, 'Amsterdam'), (2, 'The Avengers'), (3, 'Unrated'), (4, 'Other user only')");
        $connection->executeStatement(
            "INSERT INTO movie_user_watch_dates (id, movie_id, user_id, watched_at, position) VALUES
            (1, 1, 42, '2023-01-20', 1), (2, 1, 42, '2023-01-21', 1),
            (3, 2, 42, '2023-01-20', 1), (4, 3, 42, '2023-01-20', 1),
            (5, 1, 7, '2023-01-20', 1), (6, 4, 7, '2023-01-20', 1)",
        );
        $connection->executeStatement(
            'INSERT INTO movie_user_rating (movie_id, user_id, rating) VALUES
            (1, 7, 9), (2, 7, 4), (3, 7, 10), (4, 7, 10), (1, 42, 6), (2, 42, 8)',
        );
        $this->subject = new MovieRepository($connection);
    }

    public static function provideRatingFilters() : array
    {
        return [
            'all movies' => [null, null, null, [1, 2, 3], [6, 8, null]],
            'owner rating' => [true, 6, 6, [1], [6]],
            'other user rating' => [true, 9, 10, [], []],
            'rated by owner' => [true, 1, 10, [1, 2], [6, 8]],
            'unrated by owner' => [false, null, null, [3], [null]],
        ];
    }

    #[DataProvider('provideRatingFilters')]
    public function testWatchedMoviesUseOwnersRatings(
        ?bool $hasUserRating,
        ?int $min,
        ?int $max,
        array $expectedIds,
        array $expectedRatings,
    ) : void {
        $filters = [
            'userId' => 42,
            'searchTerm' => null,
            'releaseYear' => null,
            'language' => null,
            'genre' => null,
            'hasUserRating' => $hasUserRating,
            'userRatingMin' => $min,
            'userRatingMax' => $max,
            'locationId' => null,
            'productionCountryCode' => null,
        ];

        self::assertSame(count($expectedIds), $this->subject->fetchUniqueWatchedMoviesCount(...$filters));

        $movies = $this->subject->fetchUniqueWatchedMoviesPaginated(
            ...$filters + [
                'limit' => 24,
                'page' => 1,
                'sortBy' => 'title',
                'sortOrder' => SortOrder::createAsc(),
                'movieTitleDisplay' => MovieTitleDisplay::createLocalized(),
            ],
        );

        self::assertSame($expectedIds, array_column($movies, 'id'));
        self::assertSame($expectedRatings, array_column($movies, 'userRating'));
    }

    public function testWatchedMoviesSortByOwnersRatings() : void
    {
        $movies = $this->subject->fetchUniqueWatchedMoviesPaginated(
            userId: 42,
            limit: 24,
            page: 1,
            searchTerm: null,
            sortBy: 'rating',
            sortOrder: SortOrder::createDesc(),
            releaseYear: null,
            language: null,
            genre: null,
            hasUserRating: null,
            userRatingMin: null,
            userRatingMax: null,
            locationId: null,
            productionCountryCode: null,
            movieTitleDisplay: MovieTitleDisplay::createLocalized(),
        );

        self::assertSame([2, 1, 3], array_column($movies, 'id'));
        self::assertSame([8, 6, null], array_column($movies, 'userRating'));
    }
}
