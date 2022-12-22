<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\Util\Json;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\TestCase;

/** @covers \Movary\ValueObject\Date */
class DateTest extends TestCase
{
    public function create() : void
    {
        self::assertSame((new \DateTime())->format('Y-m-d'), (string)Date::create());
    }

    public function createFromDateTime() : void
    {
        $dateTime = DateTime::createFromString('2022-05-02 09:30:00');

        self::assertSame('2022-05-02', (string)Date::createFromDateTime($dateTime));
    }

    public function testCreateFromString() : void
    {
        self::assertSame('2022-05-02', (string)Date::createFromString('2022-05-02'));
    }

    public function testCreateFromStringAndFormat() : void
    {
        self::assertSame('2022-05-02', (string)Date::createFromStringAndFormat('02.05.22', 'd.m.y'));
    }

    public function testGetDifferenceInDays() : void
    {
        $subject = Date::createFromString('2022-05-02');

        self::assertSame(2, $subject->getDifferenceInDays(Date::createFromString('2022-04-30')));
        self::assertSame(30, $subject->getDifferenceInDays(Date::createFromString('2022-04-02')));
        self::assertSame(31, $subject->getDifferenceInDays(Date::createFromString('2022-06-02')));
    }

    public function testGetDifferenceInYears() : void
    {
        $subject = Date::createFromString('2022-05-02');

        self::assertSame(1, $subject->getDifferenceInYears(Date::createFromString('2021-04-02')));
        self::assertSame(2, $subject->getDifferenceInYears(Date::createFromString('2024-06-02')));
    }

    public function testJsonSerialize() : void
    {
        $subject = Date::createFromString('2022-05-02');

        self::assertSame('"2022-05-02"', Json::encode($subject));
    }
}
