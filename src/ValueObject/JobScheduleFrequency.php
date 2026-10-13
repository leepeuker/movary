<?php declare(strict_types=1);

namespace Movary\ValueObject;

use DateInterval;
use JsonSerializable;
use RuntimeException;

class JobScheduleFrequency implements JsonSerializable
{
    private const string DAILY = 'daily';

    private const string HOURLY = 'hourly';

    private const string MINUTELY = 'minutely';

    private const string MONTHLY = 'monthly';

    private const string WEEKLY = 'weekly';

    private const array SUPPORTED_FREQUENCIES = [
        self::MINUTELY,
        self::HOURLY,
        self::DAILY,
        self::WEEKLY,
        self::MONTHLY,
    ];

    private function __construct(private readonly string $frequency)
    {
        if (in_array($frequency, self::SUPPORTED_FREQUENCIES, true) === false) {
            throw new RuntimeException('Not supported job schedule frequency: ' . $frequency);
        }
    }

    public static function createFromString(string $frequency) : self
    {
        return new self($frequency);
    }

    public function __toString() : string
    {
        return $this->frequency;
    }

    public function calculateNextRun(DateTime $firstRunAt, DateTime $currentDateTime) : DateTime
    {
        if ($this->frequency === self::MONTHLY) {
            return $this->calculateNextMonthlyRun($firstRunAt, $currentDateTime);
        }

        $intervalInSeconds = match ($this->frequency) {
            self::MINUTELY => 60,
            self::HOURLY => 60 * 60,
            self::DAILY => 24 * 60 * 60,
            self::WEEKLY => 7 * 24 * 60 * 60,
            default => throw new RuntimeException('Not supported job schedule frequency: ' . $this->frequency),
        };
        $secondsSinceFirstRun = (int)$currentDateTime->format('U') - (int)$firstRunAt->format('U');
        $intervalsSinceFirstRun = max(0, intdiv($secondsSinceFirstRun, $intervalInSeconds)) + 1;

        return $firstRunAt->add(new DateInterval('PT' . ($intervalsSinceFirstRun * $intervalInSeconds) . 'S'));
    }

    private function calculateNextMonthlyRun(DateTime $firstRunAt, DateTime $currentDateTime) : DateTime
    {
        $monthDifference = ((int)$currentDateTime->format('Y') - (int)$firstRunAt->format('Y')) * 12
            + (int)$currentDateTime->format('n')
            - (int)$firstRunAt->format('n');
        $nextRun = $firstRunAt->addMonths(max(0, $monthDifference));

        if ($currentDateTime->isAfter($nextRun) || $currentDateTime->isEqual($nextRun)) {
            $nextRun = $firstRunAt->addMonths(max(0, $monthDifference) + 1);
        }

        return $nextRun;
    }

    public function jsonSerialize() : string
    {
        return $this->frequency;
    }
}
