<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\ValueObject\Gender;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\ValueObject\Gender::class)]
class GenderTest extends TestCase
{
    public function testCreateFemale() : void
    {
        $subject = Gender::createFemale();

        self::assertSame('1', (string)$subject);
        self::assertSame(1, $subject->asInt());
        self::assertSame('Female', $subject->getText());
        self::assertSame('f', $subject->getAbbreviation());
    }

    public function testCreateMale() : void
    {
        $subject = Gender::createMale();

        self::assertSame('2', (string)$subject);
        self::assertSame(2, $subject->asInt());
        self::assertSame('Male', $subject->getText());
        self::assertSame('m', $subject->getAbbreviation());
    }

    public function testCreateNonBinary() : void
    {
        $subject = Gender::createFromInt(3);

        self::assertSame('3', (string)$subject);
        self::assertSame(3, $subject->asInt());
        self::assertSame('Non Binary', $subject->getText());
        self::assertSame('nb', $subject->getAbbreviation());
    }

    public function testCreateUnknown() : void
    {
        $subject = Gender::createFromInt(0);

        self::assertSame('0', (string)$subject);
        self::assertSame(0, $subject->asInt());
        self::assertSame('Unknown', $subject->getText());
        self::assertSame(null, $subject->getAbbreviation());
    }
}
