<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie\History\Location;

use Movary\Domain\Movie\History\Location\MovieHistoryLocationEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieHistoryLocationEntity::class)]
class MovieHistoryLocationEntityTest extends TestCase
{
    public function testProvidesLocationData() : void
    {
        $location = MovieHistoryLocationEntity::createFromArray([
            'id' => '7',
            'user_id' => '12',
            'name' => 'Cinema',
            'is_cinema' => '1',
        ]);

        self::assertSame(7, $location->getId());
        self::assertSame(12, $location->getUserId());
        self::assertSame('Cinema', $location->getName());
        self::assertTrue($location->isCinema());
    }
}
