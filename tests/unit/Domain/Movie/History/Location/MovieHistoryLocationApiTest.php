<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie\History\Location;

use Movary\Domain\Movie\History\Location\MovieHistoryLocationApi;
use Movary\Domain\Movie\History\Location\MovieHistoryLocationEntityList;
use Movary\Domain\Movie\History\Location\MovieHistoryLocationRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieHistoryLocationApi::class)]
class MovieHistoryLocationApiTest extends TestCase
{
    public function testCountLocationsByUserIdReturnsRepositoryResult() : void
    {
        /** @var MovieHistoryLocationRepository&MockObject $repository */
        $repository = $this->createMock(MovieHistoryLocationRepository::class);
        $repository->expects(self::once())->method('countLocationsByUserId')->with(12)->willReturn(72);

        self::assertSame(72, (new MovieHistoryLocationApi($repository))->countLocationsByUserId(12));
    }

    public function testFindLocationsByUserIdPaginatedForwardsPagination() : void
    {
        /** @var MovieHistoryLocationRepository&MockObject $repository */
        $repository = $this->createMock(MovieHistoryLocationRepository::class);
        $locations = MovieHistoryLocationEntityList::create();
        $repository
            ->expects(self::once())
            ->method('findLocationsByUserIdPaginated')
            ->with(12, 20, 40)
            ->willReturn($locations);

        self::assertSame(
            $locations,
            (new MovieHistoryLocationApi($repository))->findLocationsByUserIdPaginated(12, 20, 40),
        );
    }
}
