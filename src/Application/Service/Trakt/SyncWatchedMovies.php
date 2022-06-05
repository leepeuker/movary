<?php declare(strict_types=1);

namespace Movary\Application\Service\Trakt;

use Movary\Api;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application;
use Movary\ValueObject\Date;
use Psr\Log\LoggerInterface;

class SyncWatchedMovies
{
    public function __construct(
        private readonly Application\Movie\Api $movieApi,
        private readonly Application\Movie\Service\Select $movieSelectService,
        private readonly Api\Trakt\Api $traktApi,
        private readonly Api\Trakt\Cache\User\Movie\Watched\Service $traktApiCacheUserMovieWatchedService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(bool $overwriteExistingData = false) : void
    {
        $watchedMovies = $this->traktApi->fetchUserMoviesWatched();

        foreach ($watchedMovies as $watchedMovie) {
            $traktId = $watchedMovie->getMovie()->getTraktId();

            $movie = $this->movieApi->findByTraktId($traktId);

            if ($movie === null) {
                $movie = $this->movieApi->create(
                    title: $watchedMovie->getMovie()->getTitle(),
                    tmdbId: $watchedMovie->getMovie()->getTmdbId(),
                    traktId: $traktId,
                    imdbId: $watchedMovie->getMovie()->getImdbId(),
                );

                $this->logger->info('Added movie: ' . $movie->getTitle());
            }

            if ($this->isWatchedCacheUpToDate($watchedMovie) === true) {
                continue;
            }

            $this->syncMovieHistory($traktId, $movie, $overwriteExistingData);

            $this->traktApiCacheUserMovieWatchedService->setOne($traktId, $watchedMovie->getLastUpdated());
        }

        if ($overwriteExistingData === true) {
            $this->removeMovieHistoryFromNotWatchedMovies($watchedMovies);
        }

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
            $traktId = $movie->getTraktId();

            if ($traktId !== null && $watchedMovies->containsTraktId($traktId) === true) {
                continue;
            }

            $this->movieApi->deleteHistoryById($movie->getId());

            $this->logger->info('Removed watch dates for movie: ' . $movie->getTitle());
        }
    }

    private function syncMovieHistory(TraktId $traktId, Application\Movie\Entity $movie, bool $overwriteExistingData) : void
    {
        $playsPerDates = [];

        foreach ($this->traktApi->fetchUserMovieHistoryByMovieId($traktId) as $movieHistoryEntry) {
            if (empty($playsPerDates[(string)Date::createFromDateTime($movieHistoryEntry->getWatchedAt())]) === true) {
                $playsPerDates[(string)Date::createFromDateTime($movieHistoryEntry->getWatchedAt())] = 1;
                continue;
            }

            $playsPerDates[(string)Date::createFromDateTime($movieHistoryEntry->getWatchedAt())]++;
        }

        foreach ($playsPerDates as $watchedAt => $playsPerDate) {
            $watchedAtObject = Date::createFromString($watchedAt);
            $currentPlays = $this->movieApi->fetchHistoryMoviePlaysOnDate($movie->getId(), $watchedAtObject);

            if ($currentPlays < $playsPerDate || ($currentPlays > $playsPerDate && $overwriteExistingData === true)) {
                $this->movieApi->replaceHistoryForMovieByDate($movie->getId(), $watchedAtObject, $playsPerDate);

                $this->logger->info('Updated plays for "' . $movie->getTitle() . '" at ' . $watchedAt . " from $currentPlays to $playsPerDate");
            }
        }
    }
}
