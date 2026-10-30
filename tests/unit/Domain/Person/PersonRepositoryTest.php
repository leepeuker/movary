<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Person;

use ArrayIterator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Movary\Domain\Person\PersonRepository;
use Movary\ValueObject\Gender;
use Movary\ValueObject\SortOrder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PersonRepository::class)]
class PersonRepositoryTest extends TestCase
{
    public function testCatalogCountAndPageUseTheSameDirectorFilters() : void
    {
        $connection = $this->createMock(Connection::class);
        $where = " WHERE p.name LIKE ? AND p.gender = ? AND EXISTS (SELECT 1 FROM movie_crew mc WHERE mc.person_id = p.id AND mc.job = 'Director')";
        $connection->expects(self::once())->method('fetchOne')
            ->with("SELECT COUNT(*) FROM `person` p$where", ['%Lee%', 0])->willReturn(0);
        $connection->expects(self::once())->method('fetchAllAssociative')
            ->with("SELECT p.* FROM `person` p$where ORDER BY p.birth_date desc, p.id ASC LIMIT 10 OFFSET 20", ['%Lee%', 0])
            ->willReturn([]);

        $subject = new PersonRepository($connection);
        self::assertSame(0, $subject->countAll('Lee', Gender::createFromInt(0), 'director'));
        self::assertSame([], $subject->fetchAllPaginated(10, 20, 'Lee', Gender::createFromInt(0), 'director', 'birthDate', SortOrder::createDesc()));
    }

    public function testActorFilterUsesMovieCast() : void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('fetchOne')
            ->with('SELECT COUNT(*) FROM `person` p WHERE EXISTS (SELECT 1 FROM movie_cast mc WHERE mc.person_id = p.id)', [])
            ->willReturn(3);

        self::assertSame(3, (new PersonRepository($connection))->countAll(null, null, 'actor'));
    }

    public function testFetchAllOrderedByLastUpdatedAtTmdbAscCanFilterNeverSyncedPersonsBeforeLimit() : void
    {
        $result = $this->createMock(Result::class);
        $result->expects(self::once())->method('iterateAssociative')->willReturn(new ArrayIterator());

        $statement = $this->createMock(Statement::class);
        $statement->expects(self::once())->method('executeQuery')->with([7, 8])->willReturn($result);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('prepare')
            ->with(
                'SELECT * FROM `person` WHERE updated_at_tmdb IS NULL AND id IN (?, ?) '
                . 'ORDER BY updated_at_tmdb, created_at LIMIT 50',
            )
            ->willReturn($statement);

        $subject = new PersonRepository($connection);

        iterator_to_array($subject->fetchAllOrderedByLastUpdatedAtTmdbAsc(50, [7, 8], true));
    }
}
