<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\ValueObject\PersonalRating;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(\Movary\ValueObject\Year::class)]
class PersonalRatingTest extends TestCase
{
    public function testAsInt() : void
    {
        $subject = PersonalRating::create(5);

        self::assertSame(5, $subject->asInt());
    }

    public function testCreateThrowsExceptionIfPersonalRatingIsHigherThanTen() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid rating: 11');

        PersonalRating::create(11);
    }

    public function testCreateThrowsExceptionIfPersonalRatingIsLowerThanOne() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid rating: 0');

        PersonalRating::create(0);
    }

    public function testIsEqual() : void
    {
        $subject = PersonalRating::create(5);

        $equalToSubject = PersonalRating::create(5);
        $notEqualToSubject = PersonalRating::create(4);

        self::assertTrue($subject->isEqual($equalToSubject));
        self::assertFalse($subject->isEqual($notEqualToSubject));
    }

    public function testToString() : void
    {
        $subject = PersonalRating::create(5);

        self::assertSame('5', (string)$subject);
    }
}
