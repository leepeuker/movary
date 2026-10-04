<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Movary\Domain\Movie\MovieRepository;
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
}
