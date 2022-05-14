<?php declare(strict_types=1);

namespace Movary\Application\Service\Trakt;

use Movary\Api;
use Movary\Application;
use Movary\ValueObject\Date;
use Psr\Log\LoggerInterface;

class SyncWatchedMovies
{
    public function __construct(
        private readonly Application\Movie\Service\Create $movieCreateService,
        private readonly Application\Movie\Service\Select $movieSelectService,
        private readonly Application\Movie\History\Service\Create $movieHistoryCreateService,
        private readonly Application\Movie\History\Service\Delete $movieHistoryDeleteService,
        private readonly Application\Movie\History\Service\Select $movieHistorySelectService,
        private readonly Api\Trakt\Api $traktApi,
        private readonly Api\Trakt\Cache\User\Movie\Watched\Service $traktApiCacheUserMovieWatchedService,
        private readonly LoggerInterface $logger
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

                $this->logger->info('Added movie: ' . $movie->getTitle());
            }

            if ($this->isWatchedCacheUpToDate($watchedMovie) === true) {
                continue;
            }

            $this->syncMovieHistory($movie, $overwriteExistingData);

            $this->traktApiCacheUserMovieWatchedService->setOne($movie->getTraktId(), $watchedMovie->getLastUpdated());
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
            if ($watchedMovies->containsTraktId($movie->getTraktId()) === false) {
                $this->movieHistoryDeleteService->deleteByMovieId($movie->getId());

                $this->logger->info('Removed watch dates for movie: ' . $movie->getTitle());
            }
        }
    }

    private function syncMovieHistory(Application\Movie\Entity $movie, bool $overwriteExistingData) : void
    {
        $playsPerDates = [];

        foreach ($this->traktApi->getUserMovieHistoryByMovieId($movie->getTraktId()) as $movieHistoryEntry) {
            if (empty($playsPerDates[(string)Date::createFromDateTime($movieHistoryEntry->getWatchedAt())]) === true) {
                $playsPerDates[(string)Date::createFromDateTime($movieHistoryEntry->getWatchedAt())] = 1;
                continue;
            }

            $playsPerDates[(string)Date::createFromDateTime($movieHistoryEntry->getWatchedAt())]++;
        }

        foreach ($playsPerDates as $watchedAt => $playsPerDate) {
            $watchedAtObject = Date::createFromString($watchedAt);
            $currentPlays = $this->movieHistorySelectService->fetchPlaysForMovieIdOnDate($movie->getId(), $watchedAtObject);

            if ($currentPlays < $playsPerDate || ($currentPlays > $playsPerDate && $overwriteExistingData === true)) {
                $this->movieHistoryCreateService->createOrUpdatePlaysForDate($movie->getId(), $watchedAtObject, $playsPerDate);

                $this->logger->info('Updated plays for "' . $movie->getTitle() . '" at ' . $watchedAt . " from $currentPlays to $playsPerDate");
            }
        }
    }
}
