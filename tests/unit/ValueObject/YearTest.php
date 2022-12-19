<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\Util\Json;
use Movary\ValueObject\Year;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** @covers \Movary\ValueObject\Year */
class YearTest extends TestCase
{
    public function testAsInt() : void
    {
        $subject = Year::createFromInt(2001);

        self::assertSame(2001, $subject->asInt());
    }

    public function testAsJsonSerialize() : void
    {
        $subject = Year::createFromInt(2001);

        self::assertSame("2001", Json::encode($subject));
    }

    public function testCreateFromInt() : void
    {
        $subject = Year::createFromInt(2001);

        self::assertSame('2001', (string)$subject);
    }

    public function testCreateFromString() : void
    {
        $subject = Year::createFromString('2001');

        self::assertSame('2001', (string)$subject);
    }

    public function testCreateThrowsExceptionIfYearValueIsToSmall() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Year has to be between 1901 and 2155, invalid value: 1501');

        Year::createFromInt(1501);
    }
}
