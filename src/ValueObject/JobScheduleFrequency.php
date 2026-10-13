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

    public function calculateNextRun(DateTime $scheduledRun, DateTime $currentDateTime) : DateTime
    {
        $nextRun = $scheduledRun;
        $interval = match ($this->frequency) {
            self::MINUTELY => new DateInterval('PT1M'),
            self::HOURLY => new DateInterval('PT1H'),
            self::DAILY => new DateInterval('P1D'),
            self::WEEKLY => new DateInterval('P1W'),
            self::MONTHLY => new DateInterval('P1M'),
        };

        do {
            $nextRun = $nextRun->add($interval);
        } while ($currentDateTime->isAfter($nextRun) || $currentDateTime->isEqual($nextRun));

        return $nextRun;
    }

    public function jsonSerialize() : string
    {
        return $this->frequency;
    }
}
