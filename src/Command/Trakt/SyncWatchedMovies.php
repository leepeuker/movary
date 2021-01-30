<?php declare(strict_types=1);

namespace Movary\Command\Trakt;

use Movary\Api;
use Movary\Application;

class SyncWatchedMovies
{
    private Application\Movie\Service\Create $movieCreateService;

    private Application\Movie\History\Service\Create $movieHistoryCreateService;

    private Application\Movie\History\Service\Delete $movieHistoryDeleteService;

    private Application\Movie\Service\Select $movieSelectService;

    private Api\Trakt\Api $traktApi;

    private Api\Trakt\Cache\User\Movie\Watched\Service $traktApiCacheUserMovieWatchedService;

    public function __construct(
        Application\Movie\Service\Create $movieCreateService,
        Application\Movie\Service\Select $movieSelectService,
        Application\Movie\History\Service\Create $movieHistoryCreateService,
        Application\Movie\History\Service\Delete $movieHistoryDeleteService,
        Api\Trakt\Api $traktApi,
        Api\Trakt\Cache\User\Movie\Watched\Service $traktApiCacheUserMovieWatchedService
    ) {
        $this->movieCreateService = $movieCreateService;
        $this->movieSelectService = $movieSelectService;
        $this->movieHistoryCreateService = $movieHistoryCreateService;
        $this->movieHistoryDeleteService = $movieHistoryDeleteService;
        $this->traktApi = $traktApi;
        $this->traktApiCacheUserMovieWatchedService = $traktApiCacheUserMovieWatchedService;
    }

    public function run() : void
    {
        $watchedMovies = $this->traktApi->getUserMoviesWatched('leepe');

        foreach ($watchedMovies as $watchedMovie) {
            $movie = $this->movieSelectService->findByTraktId($watchedMovie->getMovie()->getTraktId());

            if ($movie === null) {
                $movie = $this->movieCreateService->create(
                    $watchedMovie->getMovie()->getTitle(),
                    $watchedMovie->getMovie()->getYear(),
                    null,
                    $watchedMovie->getMovie()->getTraktId(),
                    $watchedMovie->getMovie()->getImdbId(),
                    $watchedMovie->getMovie()->getTmdbId(),
                );

                echo 'Added movie: ' . $movie->getTitle() . "\n";
            } elseif ($this->isWatchedCacheUpToDate($watchedMovie) === true) {
                continue;
            }

            $this->syncMovieHistory($movie);
        }

        $this->removeMovieHistoryFromNotWatchedMovies($watchedMovies);

        $this->traktApiCacheUserMovieWatchedService->set($watchedMovies);
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

                echo 'Removed watch dates for movie: ' . $movie->getTitle() . "\n";
            }
        }
    }

    private function syncMovieHistory(Application\Movie\Entity $movie) : void
    {
        $this->movieHistoryDeleteService->deleteByMovieId($movie->getId());

        foreach ($this->traktApi->getUserMovieHistoryByMovieId('leepe', $movie->getTraktId()) as $movieHistoryEntry) {
            $this->movieHistoryCreateService->create($movie->getId(), $movieHistoryEntry->getWatchedAt());

            echo 'Added watch date for "' . $movieHistoryEntry->getMovie()->getTitle() . '": ' . $movieHistoryEntry->getWatchedAt() . "\n";
        }
    }
}
