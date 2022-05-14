<?php declare(strict_types=1);

namespace Movary\Application\Service\Trakt;

use Movary\Api;
use Movary\Application;
use Movary\ValueObject\Date;

class SyncWatchedMovies
{
    public function __construct(
        private readonly Application\Movie\Service\Create $movieCreateService,
        private readonly Application\Movie\Service\Select $movieSelectService,
        private readonly Application\Movie\History\Service\Create $movieHistoryCreateService,
        private readonly Application\Movie\History\Service\Delete $movieHistoryDeleteService,
        private readonly Application\Movie\History\Service\Select $movieHistorySelectService,
        private readonly Api\Trakt\Api $traktApi,
        private readonly Api\Trakt\Cache\User\Movie\Watched\Service $traktApiCacheUserMovieWatchedService
    ) {
    }

    public function execute(bool $overwriteExistingData = false) : void
    {
        $watchedMovies = $this->traktApi->getUserMoviesWatched();

        foreach ($watchedMovies as $watchedMovie) {
            $movie = $this->movieSelectService->findByTraktId($watchedMovie->getMovie()->getTraktId());

            if ($movie === null) {
                $movie = $this->movieCreateService->create(
                    $watchedMovie->getMovie()->getTitle(),
                    null,
                    null,
                    $watchedMovie->getMovie()->getTraktId(),
                    $watchedMovie->getMovie()->getImdbId(),
                    $watchedMovie->getMovie()->getTmdbId(),
                );
                // echo 'Added movie: ' . $movie->getTitle() . PHP_EOL;
            }

            if ($this->isWatchedCacheUpToDate($watchedMovie) === true) {
                continue;
            }

            $this->syncMovieHistory($movie, $overwriteExistingData);

            $this->traktApiCacheUserMovieWatchedService->setOne($movie->getTraktId(), $watchedMovie->getLastUpdated());
        }

        $this->removeMovieHistoryFromNotWatchedMovies($watchedMovies);

        $this->traktApiCacheUserMovieWatchedService->removeMissingMoviesFromCache($watchedMovies);
    }

    private function isWatchedCacheUpToDate(Api\Trakt\ValueObject\User\Movie\Watched\Dto $watchedMovie) : bool
    {
        $cacheLastUpdated = $this->traktApiCacheUserMovieWatchedService->findLastUpdatedByTraktId($watchedMovie->getMovie()->getTraktId());

        return $cacheLastUpdated !== null && $watchedMovie->getLastUpdated()->isEqual($cacheLastUpdated) === true;
    }

    private function removeMovieHistoryFromNotWatchedMovies(Api\Trakt\ValueObject\User\Movie\Watched\DtoList $watchedMovies) : void
    {
        foreach ($this->movieSelectService->fetchAll() as $movie) {
            if ($watchedMovies->containsTraktId($movie->getTraktId()) === false) {
                $this->movieHistoryDeleteService->deleteByMovieId($movie->getId());
                // echo 'Removed watch dates for movie: ' . $movie->getTitle() . PHP_EOL;
            }
        }
    }

    private function syncMovieHistory(Application\Movie\Entity $movie, bool $overwriteExistingData) : void
    {
        if ($overwriteExistingData === true) {
            $this->movieHistoryDeleteService->deleteByMovieId($movie->getId());
        }

        $playsPerDates = [];

        foreach ($this->traktApi->getUserMovieHistoryByMovieId($movie->getTraktId()) as $movieHistoryEntry) {
            if (isset($playsPerDates[(string)Date::createFromDateTime($movieHistoryEntry->getWatchedAt())]) === false) {
                $playsPerDates[(string)Date::createFromDateTime($movieHistoryEntry->getWatchedAt())] = 1;
                continue;
            }

            $playsPerDates[(string)Date::createFromDateTime($movieHistoryEntry->getWatchedAt())]++;
            // echo 'Added watch date for "' . $movieHistoryEntry->getMovie()->getTitle() . '": ' . $movieHistoryEntry->getWatchedAt() . PHP_EOL;
        }

        foreach ($playsPerDates as $watchedAt => $playsPerDate) {
            $watchedAtObject = Date::createFromString($watchedAt);
            $currentPlays = $this->movieHistorySelectService->fetchPlaysForMovieIdOnDate($movie->getId(), $watchedAtObject);

            if ($currentPlays <= $playsPerDate) {
                $this->movieHistoryCreateService->createOrUpdatePlaysForDate($movie->getId(), $watchedAtObject, $playsPerDate);
            }
        }
    }
}
