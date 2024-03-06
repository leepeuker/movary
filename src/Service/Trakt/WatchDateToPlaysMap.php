<?php declare(strict_types=1);

namespace Movary\Service\Trakt;

use Movary\ValueObject\AbstractList;
use Movary\ValueObject\Date;
use RuntimeException;

/**
 * @method array<string, int> getIterator()
 * @psalm-suppress ImplementedReturnTypeMismatch
 */
class WatchDateToPlaysMap extends AbstractList
{
    public static function create() : self
    {
        return new self();
    }

    public function add(Date $watchDate, int $plays) : void
    {
        if ($this->containsDate($watchDate) === true) {
            throw new RuntimeException('Cannot add date date, date already exists.');
        }

        $this->data[(string)$watchDate] = $plays;
    }

    public function containsDate(Date $watchDate) : bool
    {
        return isset($this->data[(string)$watchDate]) === true;
    }

    public function getPlaysForDate(Date $watchDate) : int
    {
        if ($this->containsDate($watchDate) === false) {
            throw new RuntimeException('Cannot get plays for missing date: ' . $watchDate);
        }

        return $this->data[(string)$watchDate];
    }

    public function incrementPlaysForDate(Date $watchDate) : void
    {
        if ($this->containsDate($watchDate) === false) {
            $this->data[(string)$watchDate] = 1;

            return;
        }

        $this->data[(string)$watchDate]++;
    }

    public function removeWatchDates(WatchDateToPlaysMap $filteredWatchDateToPlayCountMap) : self
    {
        $filteredList = self::create();

        foreach ($this as $watchDate => $plays) {
            $watchDate = Date::createFromString($watchDate);

            if ($filteredWatchDateToPlayCountMap->containsDate($watchDate) === true) {
                continue;
            }

            $filteredList->add($watchDate, $plays);
        }

        return $filteredList;
    }
}
