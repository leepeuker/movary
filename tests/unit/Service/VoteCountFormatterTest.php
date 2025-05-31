<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Movary\Service\VoteCountFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Service\VoteCountFormatter::class)]
class VoteCountFormatterTest extends TestCase
{
    private VoteCountFormatter $subject;

    public static function provideTestData() : array
    {
        return [
            [null, '-'],
            [1, '1'],
            [999, '999'],
            [1000, '1K'],
            [5449, '5.4K'],
            [5450, '5.5K'],
            [9949, '9.9K'],
            [9950, '10K'],
            [10000, '10K'],
            [50000, '50K'],
            [100000, '100K'],
            [549900, '550K'],
            [999499, '999K'],
            [999500, '1M'],
            [1000000, '1M'],
            [1100000, '1.1M'],
            [1149999, '1.1M'],
            [1150000, '1.2M'],
        ];
    }

    public function setUp() : void
    {
        $this->subject = new VoteCountFormatter();
    }

    #[DataProvider('provideTestData')]
    public function testFormat(?int $voteCount, string $expectedResult) : void
    {
        self::assertSame($expectedResult, $this->subject->formatVoteCount($voteCount));
    }
}
