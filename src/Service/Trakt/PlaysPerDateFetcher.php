<?php declare(strict_types=1);

namespace Movary\Service\Trakt;

use Movary\Api\Trakt\TraktApi;
use Movary\Api\Trakt\ValueObject\TraktCredentials;
use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\ValueObject\Date;

class PlaysPerDateFetcher
{
    public function __construct(private readonly TraktApi $traktApi)
    {
    }

    public function fetchTraktPlaysPerDate(TraktCredentials $traktCredentials, TraktId $traktMovieId) : WatchDateToPlaysMap
    {
        $playsPerDates = WatchDateToPlaysMap::create();

        foreach ($this->traktApi->fetchUserMovieHistoryByMovieId($traktCredentials, $traktMovieId) as $movieHistoryEntry) {
            $watchDate = Date::createFromDateTime($movieHistoryEntry->getWatchedAt());

            $playsPerDates->incrementPlaysForDate($watchDate);
        }

        return $playsPerDates;
    }
}
