<?php declare(strict_types=1);

namespace Movary\Application\Service\Trakt;

use Movary\Api\Trakt\Api;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\ValueObject\Date;

class PlaysPerDateFetcher
{
    public function __construct(private readonly Api $traktApi)
    {
    }

    public function fetchTraktPlaysPerDate(TraktId $traktId) : PlaysPerDateDtoList
    {
        $playsPerDates = PlaysPerDateDtoList::create();

        foreach ($this->traktApi->fetchUserMovieHistoryByMovieId($traktId) as $movieHistoryEntry) {
            $watchDate = Date::createFromDateTime($movieHistoryEntry->getWatchedAt());

            $playsPerDates->incrementPlaysForDate($watchDate);
        }

        return $playsPerDates;
    }
}
