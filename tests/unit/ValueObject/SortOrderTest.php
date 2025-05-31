<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\ValueObject\SortOrder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\ValueObject\SortOrder::class)]
class SortOrderTest extends TestCase
{
    public function testCreateAsc() : void
    {
        $subject = SortOrder::createAsc();

        self::assertSame('asc', (string)$subject);
    }

    public function testCreateDesc() : void
    {
        $subject = SortOrder::createDesc();

        self::assertSame('desc', (string)$subject);
    }
}
