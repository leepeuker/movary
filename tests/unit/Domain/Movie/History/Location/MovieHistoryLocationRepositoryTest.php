<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie\History\Location;

use Doctrine\DBAL\Connection;
use Movary\Domain\Movie\History\Location\MovieHistoryLocationRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieHistoryLocationRepository::class)]
class MovieHistoryLocationRepositoryTest extends TestCase
{
    public function testCountLocationsByUserIdReturnsIntegerCount() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM `location` WHERE user_id = ?', [12])
            ->willReturn('72');

        self::assertSame(72, (new MovieHistoryLocationRepository($connection))->countLocationsByUserId(12));
    }

    public function testFindLocationsByUserIdPaginatedAppliesLimitAndOffset() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                self::callback(
                    static fn(string $query) : bool => str_contains($query, 'WHERE `location`.user_id = ?')
                        && str_contains($query, 'ORDER BY `location`.name')
                        && str_contains($query, 'SUM(plays)')
                        && str_contains($query, 'LIMIT 20 OFFSET 40'),
                ),
                [12],
            )
            ->willReturn([
                ['id' => '7', 'user_id' => '12', 'name' => 'Cinema', 'is_cinema' => '1', 'plays' => '23'],
            ]);

        $locations = (new MovieHistoryLocationRepository($connection))->findLocationsByUserIdPaginated(
            12,
            20,
            40,
        );

        self::assertCount(1, $locations);
        self::assertSame(7, $locations->asArray()[0]->getId());
        self::assertSame(23, $locations->asArray()[0]->getPlays());
    }
}
