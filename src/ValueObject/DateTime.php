<?php declare(strict_types=1);

namespace Movary\ValueObject;

class DateTime implements \JsonSerializable
{
    private const DEFAULT_TIME_ZONE = 'UTC';

    private const FORMAT = 'Y-m-d H:i:s';

    private function __construct(public string $dateTime)
    {
    }

    public static function create() : self
    {
        return self::createFromString('now');
    }

    public static function createFromString(string $dateTimeString) : self
    {
        return new self((new \DateTime($dateTimeString, new \DateTimeZone(self::DEFAULT_TIME_ZONE)))->format(self::FORMAT));
    }

    public static function createFromStringAndTimeZone(string $dateTimeString, string $timeZone) : self
    {
        return new self((new \DateTime($dateTimeString, new \DateTimeZone($timeZone)))->format(self::FORMAT));
    }

    public function __toString() : string
    {
        return $this->dateTime;
    }

    public function diff(DateTime $dateTime) : DateInterval
    {
        $dateInterval = (new \DateTime($this->dateTime))->diff(new \DateTime((string)$dateTime));

        return DateInterval::createByDateInterval($dateInterval);
    }

    public function isEqual(DateTime $lastUpdated) : bool
    {
        return (string)$this === (string)$lastUpdated;
    }

    public function jsonSerialize() : string
    {
        return $this->dateTime;
    }
}
