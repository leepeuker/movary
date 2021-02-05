<?php declare(strict_types=1);

namespace Movary\ValueObject;

class DateInterval
{
    private \DateInterval $dateInterval;

    private function __construct(\DateInterval $dateInterval)
    {
        $this->dateInterval = $dateInterval;
    }

    public static function createByDateInterval(\DateInterval $dateInterval) : self
    {
        return new self($dateInterval);
    }

    public function getDays() : int
    {
        return $this->dateInterval->d;
    }
}
