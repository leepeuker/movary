<?php declare(strict_types=1);

namespace Movary\ValueObject;

class Date implements \JsonSerializable
{
    private const FORMAT = 'Y-m-d';

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
        return new self($dateString);
    }

    public function __toString() : string
    {
        return $this->date;
    }

    public function getNumberOfDaysSince(Date $date) : int
    {
        $daysSince = (new \DateTime($this->date))->diff((new \DateTime($date->date)))->days;

        if ($daysSince === false) {
            throw new \RuntimeException('Could not get number of days since: ' . $date);
        }

        return $daysSince;
    }

    public function jsonSerialize() : string
    {
        return $this->date;
    }
}
