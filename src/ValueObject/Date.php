<?php declare(strict_types=1);

namespace Movary\ValueObject;

class Date implements \JsonSerializable
{
    private const FORMAT = 'Y-m-d';

    private function __construct(public readonly string $date)
    {
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

    public function jsonSerialize() : string
    {
        return $this->date;
    }
}
