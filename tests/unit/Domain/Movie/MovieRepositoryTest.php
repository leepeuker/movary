<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Movary\Domain\Movie\MovieRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/** @covers \Movary\Domain\Movie\MovieRepository */
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

    /**
     * @dataProvider providedTestFetchMovieIdsHavingImdbIdOrderedByLastImdbUpdatedAtData
     */
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
}
