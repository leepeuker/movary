<?php declare(strict_types=1);

namespace Movary\Service\Trakt;

use Movary\Api;
use Movary\Api\Trakt\TraktApi;
use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\User\UserApi;
use Movary\JobQueue\JobEntity;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Service\Trakt\Exception\TraktClientIdNotSet;
use Movary\Service\Trakt\Exception\TraktUserNameNotSet;
use Movary\ValueObject\Date;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ImportWatchedMovies
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly TraktApi $traktApi,
        private readonly Api\Trakt\Cache\User\Movie\Watched\Service $traktApiCacheUserMovieWatchedService,
        private readonly LoggerInterface $logger,
        private readonly PlaysPerDateFetcher $playsPerDateFetcher,
        private readonly SyncMovie $tmdbMovieSync,
        private readonly UserApi $userApi,
    ) {
    }

    public function execute(int $userId, bool $overwriteExistingData = false, bool $ignoreCache = false) : void
    {
        $traktClientId = $this->userApi->findTraktClientId($userId);
        if ($traktClientId === null) {
            throw new TraktClientIdNotSet();
        }

        $traktUserName = $this->userApi->findTraktUserName($userId);
        if ($traktUserName === null) {
            throw new TraktUserNameNotSet();
        }

        $watchedMovies = $this->traktApi->fetchUserMoviesWatched($traktClientId, $traktUserName);

        foreach ($watchedMovies as $watchedMovie) {
            $traktId = $watchedMovie->getMovie()->getTraktId();

            $movie = $this->findOrCreateMovieLocally($watchedMovie->getMovie());

            if ($ignoreCache === false && $this->isWatchedCacheUpToDate($userId, $watchedMovie) === true) {
                continue;
            }

            $this->importMovieHistory($traktClientId, $traktUserName, $userId, $traktId, $movie, $overwriteExistingData);

            $this->traktApiCacheUserMovieWatchedService->setOne($userId, $traktId, $watchedMovie->getLastUpdated());
        }

        foreach ($this->traktApi->fetchUniqueCachedTraktIds($userId) as $traktId) {
            if ($watchedMovies->containsTraktId($traktId) === false) {
                if ($overwriteExistingData === true) {
                    $this->movieApi->deleteHistoryForUserByTraktId($userId, $traktId);

                    $this->logger->info('Removed watch dates for movie with trakt id: ' . $traktId);
                }

                $this->traktApi->removeWatchCacheByTraktId($userId, $traktId);
            }
        }
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing userId');
        }

        $this->execute($userId);
    }

    private function findOrCreateMovieLocally(Api\Trakt\ValueObject\TraktMovie $watchedMovie) : MovieEntity
    {
        $traktId = $watchedMovie->getTraktId();
        $tmdbId = $watchedMovie->getTmdbId();

        $movie = $this->movieApi->findByTraktId($traktId);

        if ($movie !== null) {
            return $movie;
        }

        $movie = $this->movieApi->findByTmdbId($tmdbId);

        if ($movie !== null) {
            $this->movieApi->updateTraktId($movie->getId(), $traktId);

            return $this->movieApi->fetchByTraktId($traktId);
        }

        $movie = $this->tmdbMovieSync->syncMovie($tmdbId);
        $this->movieApi->updateTraktId($movie->getId(), $traktId);

        $this->logger->info('Added movie: ' . $movie->getTitle());

        return $this->movieApi->fetchByTraktId($traktId);
    }

    private function importMovieHistory(
        string $traktClientId,
        string $traktUserName,
        int $userId,
        TraktId $traktId,
        MovieEntity $movie,
        bool $overwriteExistingData,
    ) : void {
        $traktHistoryEntries = $this->playsPerDateFetcher->fetchTraktPlaysPerDate($traktClientId, $traktUserName, $traktId);

        foreach ($this->movieApi->fetchHistoryByMovieId($movie->getId(), $userId) as $localHistoryEntry) {
            $localHistoryEntryDate = Date::createFromString($localHistoryEntry['watched_at']);

            if ($traktHistoryEntries->containsDate($localHistoryEntryDate) === false) {
                if ($overwriteExistingData === false) {
                    continue;
                }

                $this->movieApi->deleteHistoryByIdAndDate($movie->getId(), $userId, $localHistoryEntryDate);

                continue;
            }

            $localHistoryEntryPlays = $localHistoryEntry['plays'];
            $traktHistoryEntryPlays = $traktHistoryEntries->getPlaysForDate($localHistoryEntryDate);

            if ($localHistoryEntryPlays < $traktHistoryEntryPlays || ($localHistoryEntryPlays > $traktHistoryEntryPlays && $overwriteExistingData === true)) {
                $this->movieApi->replaceHistoryForMovieByDate($movie->getId(), $userId, $localHistoryEntryDate, $traktHistoryEntryPlays);

                $this->logger->info('Updated plays for "' . $movie->getTitle() . '" at ' . $localHistoryEntryDate . " from $localHistoryEntryPlays to $traktHistoryEntryPlays");
            }

            $traktHistoryEntries->removeDate($localHistoryEntryDate);
        }

        foreach ($traktHistoryEntries as $watchedAt => $plays) {
            $localHistoryEntryDate = Date::createFromString($watchedAt);

            $this->movieApi->replaceHistoryForMovieByDate($movie->getId(), $userId, $localHistoryEntryDate, $plays);

            $this->logger->info('Added plays for "' . $movie->getTitle() . '" at ' . $watchedAt . " with $plays");
        }
    }

    private function isWatchedCacheUpToDate(int $userId, Api\Trakt\ValueObject\User\Movie\Watched\Dto $watchedMovie) : bool
    {
        $cacheLastUpdated = $this->traktApiCacheUserMovieWatchedService->findLastUpdatedByTraktId($userId, $watchedMovie->getMovie()->getTraktId());

        return $cacheLastUpdated !== null && $watchedMovie->getLastUpdated()->isEqual($cacheLastUpdated) === true;
    }
}
