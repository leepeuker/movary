<?php declare(strict_types=1);

namespace Movary\ValueObject;

use JsonSerializable;
use RuntimeException;

class Date implements JsonSerializable
{
    private const string FORMAT = 'Y-m-d';

    private function __construct(private readonly string $date)
    {
    }

    public static function create() : self
    {
        return new self ((new \DateTime())->format(self::FORMAT));
    }

    public static function createFromDateTime(DateTime $dateTime) : self
    {
        return new self ((new \DateTime((string)$dateTime))->format(self::FORMAT));
    }

    public static function createFromString(string $dateString) : self
    {
        return new self ((new \DateTime($dateString))->format(self::FORMAT));
    }

    public static function createFromStringAndFormat(string $dateString, string $dateFormat) : self
    {
        $dateTime = \DateTime::createFromFormat($dateFormat, $dateString);

        if ($dateTime === false) {
            throw new RuntimeException(sprintf('Could not create datetime of string "%s" with format "%s".', $dateString, $dateFormat));
        }

        return new self ($dateTime->format(self::FORMAT));
    }

    public function __toString() : string
    {
        return $this->date;
    }

    public function format(string $format) : string
    {
        return (new \DateTime($this->date))->format($format);
    }

    public function getDifferenceInDays(Date $date) : int
    {
        $daysSince = (new \DateTime($this->date))->diff((new \DateTime($date->date)))->days;

        if ($daysSince === false) {
            throw new RuntimeException('Could not get number of days since: ' . $date);
        }

        return $daysSince;
    }

    public function getDifferenceInYears(Date $date) : int
    {
        return (new \DateTime($this->date))->diff((new \DateTime($date->date)))->y;
    }

    public function isEqual(self $lastWatchDate) : bool
    {
        return $this->date === $lastWatchDate->date;
    }

    public function jsonSerialize() : string
    {
        return $this->date;
    }
}
