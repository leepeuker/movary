<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\ValueObject\DateTime;
use Movary\ValueObject\JobScheduleFrequency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobScheduleFrequency::class)]
class JobScheduleFrequencyTest extends TestCase
{
    #[DataProvider('provideFrequencies')]
    public function testCalculateNextRunSkipsMissedOccurrences(
        string $frequency,
        string $scheduledRun,
        string $currentDateTime,
        string $expectedNextRun,
    ) : void {
        $nextRun = JobScheduleFrequency::createFromString($frequency)->calculateNextRun(
            DateTime::createFromString($scheduledRun),
            DateTime::createFromString($currentDateTime),
        );

        self::assertSame($expectedNextRun, (string)$nextRun);
    }

    public static function provideFrequencies() : iterable
    {
        yield 'minutely' => ['minutely', '2026-10-13 12:00:00', '2026-10-13 12:02:30', '2026-10-13 12:03:00'];
        yield 'hourly' => ['hourly', '2026-10-13 12:00:00', '2026-10-13 14:30:00', '2026-10-13 15:00:00'];
        yield 'daily' => ['daily', '2026-10-13 12:00:00', '2026-10-15 12:00:00', '2026-10-16 12:00:00'];
        yield 'weekly' => ['weekly', '2026-10-13 12:00:00', '2026-10-20 11:00:00', '2026-10-20 12:00:00'];
        yield 'monthly' => ['monthly', '2026-10-13 12:00:00', '2026-11-14 12:00:00', '2026-12-13 12:00:00'];
        yield 'monthly on last day' => ['monthly', '2026-01-31 12:00:00', '2026-02-28 12:00:00', '2026-03-31 12:00:00'];
    }
}
