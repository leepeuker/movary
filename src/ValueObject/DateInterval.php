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
        $days = $this->dateInterval->days;

        if ($days === false) {
            throw new \RuntimeException('Could not get days');
        }

        return $days;
    }

    public function getHours() : int
    {
        return $this->dateInterval->h;
    }
}
