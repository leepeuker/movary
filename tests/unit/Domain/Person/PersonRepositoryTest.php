<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Person;

use ArrayIterator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Movary\Domain\Person\PersonRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PersonRepository::class)]
class PersonRepositoryTest extends TestCase
{
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
