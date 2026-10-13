<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\Util\Json;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\ValueObject\DateTime::class)]
class DateTimeTest extends TestCase
{
    public function testDiffInHours() : void
    {
        $subject = DateTime::createFromString('2022-05-02 13:02:50');

        $dateTimeSame = DateTime::createFromString('2022-05-02 13:02:50');
        $dateTimeBefore = DateTime::createFromString('2022-05-03 13:02:50');
        $dateTimeAfter = DateTime::createFromString('2022-05-01 05:02:50');

        self::assertSame(0, $subject->differenceInHours($dateTimeSame));
        self::assertSame(-24, $subject->differenceInHours($dateTimeBefore));
        self::assertSame(32, $subject->differenceInHours($dateTimeAfter));
    }

    public function testFormat() : void
    {
        $subject = DateTime::createFromString('2022-05-02 13:02:50');

        self::assertSame('13:02:50 2022-05-02', $subject->format('H:i:s Y-m-d'));
    }

    public function testIsAfter() : void
    {
        $subject = DateTime::createFromString('2022-05-02 13:02:50');

        $dateTimeBefore = DateTime::createFromString('2022-05-03 13:02:50');
        $dateTimeAfter = DateTime::createFromString('2022-05-01 13:02:50');

        self::assertFalse($subject->isAfter($subject));
        self::assertFalse($subject->isAfter($dateTimeBefore));
        self::assertTrue($subject->isAfter($dateTimeAfter));
    }

    public function testIsEqual() : void
    {
        $subject = DateTime::createFromString('2022-05-02 13:02:50');

        $dateTimeSame = DateTime::createFromString('2022-05-02 13:02:50');
        $dateTimeBefore = DateTime::createFromString('2022-05-03 13:02:50');
        $dateTimeAfter = DateTime::createFromString('2022-05-01 13:02:50');

        self::assertTrue($subject->isEqual($dateTimeSame));
        self::assertFalse($subject->isEqual($dateTimeBefore));
        self::assertFalse($subject->isEqual($dateTimeAfter));
    }

    public function testJsonSerialize() : void
    {
        $subject = DateTime::createFromString('2022-05-02 13:02:50');

        self::assertSame('"2022-05-02 13:02:50"', Json::encode($subject));
    }
}
