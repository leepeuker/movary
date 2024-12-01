<?php declare(strict_types=1);

namespace Movary\Service\Trakt;

use Movary\ValueObject\AbstractMap;
use Movary\ValueObject\Date;
use RuntimeException;

/**
 * @extends AbstractMap<Date, int>
 * @method Date key()
 * @psalm-suppress MethodSignatureMustProvideReturnType
 */
class WatchDateToPlaysMap extends AbstractMap
{
    public static function create() : self
    {
        return new self();
    }

    public function set(Date $watchDate, int $plays) : void
    {
        $this->setKeyAndValue($watchDate, $plays);
    }

    public function get(Date $watchDate) : ?int
    {
        return $this->findByKey($watchDate);
    }

    public function containsDate(Date $watchDate) : bool
    {
        return isset($this->data[(string)$watchDate]) === true;
    }

    public function getPlaysForDate(Date $watchDate) : int
    {
        $plays = $this->get($watchDate);

        if ($plays === null) {
            throw new RuntimeException('Cannot get plays for missing date: ' . $watchDate);
        }

        return $plays;
    }

    public function incrementPlaysForDate(Date $watchDate) : void
    {
        $plays = $this->get($watchDate);

        if ($plays === null) {
            $this->set($watchDate, 1);

            return;
        }

        $this->set($watchDate, $plays + 1);
    }

    public function removeWatchDates(WatchDateToPlaysMap $filteredWatchDateToPlayCountMap) : self
    {
        $filteredList = self::create();

        foreach ($this as $watchDate => $plays) {
            $watchDate = Date::createFromString((string)$watchDate);

            if ($filteredWatchDateToPlayCountMap->containsDate($watchDate) === true) {
                continue;
            }

            $filteredList->set($watchDate, $plays);
        }

        return $filteredList;
    }
}
