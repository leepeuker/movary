<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\ValueObject\ImdbRating;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\ValueObject\ImdbRating::class)]
class ImdbRatingTest extends TestCase
{
    private ImdbRating $subject;

    public function setUp() : void
    {
        $this->subject = ImdbRating::create(2.4, 1342);
    }

    public function testGetRating() : void
    {
        self::assertSame(2.4, $this->subject->getRating());
    }

    public function testGetVotesCount() : void
    {
        self::assertSame(1342, $this->subject->getVotesCount());
    }
}
