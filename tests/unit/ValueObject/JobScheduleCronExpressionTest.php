<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use InvalidArgumentException;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\JobScheduleCronExpression;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobScheduleCronExpression::class)]
class JobScheduleCronExpressionTest extends TestCase
{
    #[DataProvider('provideExpressions')]
    public function testCalculateNextRunSkipsMissedOccurrences(
        string $expression,
        string $currentDateTime,
        string $expectedNextRun,
    ) : void {
        $nextRun = JobScheduleCronExpression::createFromString($expression)->calculateNextRun(
            DateTime::createFromString($currentDateTime),
        );

        self::assertSame($expectedNextRun, (string)$nextRun);
    }

    public static function provideExpressions() : iterable
    {
        yield 'every minute' => ['* * * * *', '2026-10-13 12:02:30', '2026-10-13 12:03:00'];
        yield 'every fifteen minutes' => ['*/15 * * * *', '2026-10-13 12:02:30', '2026-10-13 12:15:00'];
        yield 'hourly' => ['0 * * * *', '2026-10-13 14:30:00', '2026-10-13 15:00:00'];
        yield 'daily' => ['0 12 * * *', '2026-10-15 12:00:00', '2026-10-16 12:00:00'];
        yield 'weekly' => ['0 12 * * 2', '2026-10-20 11:00:00', '2026-10-20 12:00:00'];
        yield 'monthly' => ['0 12 13 * *', '2026-11-14 12:00:00', '2026-12-13 12:00:00'];
        yield 'month without requested day' => ['0 12 31 * *', '2026-02-28 12:00:00', '2026-03-31 12:00:00'];
    }

    public function testNormalizesWhitespace() : void
    {
        self::assertSame('0 12 * * 1', (string)JobScheduleCronExpression::createFromString('  0  12  * *  1 '));
    }

    #[DataProvider('provideInvalidExpressions')]
    public function testRejectsInvalidExpression(string $expression) : void
    {
        $this->expectException(InvalidArgumentException::class);

        JobScheduleCronExpression::createFromString($expression);
    }

    public static function provideInvalidExpressions() : iterable
    {
        yield 'empty' => [''];
        yield 'four fields' => ['0 12 * *'];
        yield 'year field' => ['0 12 * * * 2027'];
        yield 'invalid minute' => ['60 12 * * *'];
    }
}
