<?php declare(strict_types=1);

namespace Movary\Service\Trakt;

use Movary\Api;
use Movary\Api\Trakt\TraktApi;
use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\User\UserApi;
use Movary\JobQueue\JobEntity;
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
        private readonly UserApi $userApi,
        private readonly MovieImporter $movieImporter,
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

        $traktWatchedMovies = $this->traktApi->fetchUserMoviesWatched($traktClientId, $traktUserName);

        foreach ($this->traktApi->fetchUserMoviesWatched($traktClientId, $traktUserName) as $watchedMovie) {
            $traktId = $watchedMovie->getMovie()->getTraktId();

            $movie = $this->movieImporter->importMovie($watchedMovie->getMovie());

            if ($ignoreCache === false && $this->isWatchedCacheUpToDate($userId, $watchedMovie) === true) {
                $this->logger->debug('Trakt history import: Skipped "' . $movie->getTitle() . '" because trakt cache is up to date');

                continue;
            }

            $this->importMovieHistory($traktClientId, $traktUserName, $userId, $traktId, $movie, $overwriteExistingData);

            $this->traktApiCacheUserMovieWatchedService->setOne($userId, $traktId, $watchedMovie->getLastUpdated());
        }

        $this->removeWatchesNoLongerExistingInTrakt($userId, $traktWatchedMovies, $overwriteExistingData);
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing userId');
        }

        $this->execute($userId);
    }

    private function deleteWatchDate(MovieEntity $movie, int $userId, Date $watchDate, bool $overwriteExistingData) : void
    {
        if ($overwriteExistingData === false) {
            return;
        }

        $this->movieApi->deleteHistoryByIdAndDate($movie->getId(), $userId, $watchDate);

        $this->logger->info(sprintf('Trakt history import: Deleted watch dates not existing in trakt for movie %s at %s', $movie->getTitle(), $watchDate));
    }

    private function importMovieHistory(
        string $traktClientId,
        string $traktUserName,
        int $userId,
        TraktId $traktId,
        MovieEntity $movie,
        bool $overwriteExistingData,
    ) : void {
        //
        $latestTraktWatchDateToPlaysMap = $this->playsPerDateFetcher->fetchTraktPlaysPerDate($traktClientId, $traktUserName, $traktId);

        $skipWatchDates = WatchDateToPlaysMap::create();

        foreach ($this->movieApi->fetchHistoryByMovieId($movie->getId(), $userId) as $localHistoryEntry) {
            $localWatchDate = Date::createFromString($localHistoryEntry['watched_at']);

            if ($latestTraktWatchDateToPlaysMap->containsDate($localWatchDate) === false) {
                $this->deleteWatchDate($movie, $userId, $localWatchDate, $overwriteExistingData);

                continue;
            }

            $localWatchDatePlays = $localHistoryEntry['plays'];
            $latestTraktWatchDatePlays = $latestTraktWatchDateToPlaysMap->getPlaysForDate($localWatchDate);

            if ($localWatchDatePlays === $latestTraktWatchDatePlays) {
                $this->logger->debug('Trakt history import: Skipped "' . $movie->getTitle() . '" watch date "' . $localWatchDate . '" plays update, already up to date');

                $skipWatchDates->add($localWatchDate, $localWatchDatePlays);

                continue;
            }

            if ($overwriteExistingData === false) {
                $this->logger->debug('Trakt history import: Skipped "' . $movie->getTitle() . '" watch date "' . $localWatchDate . '" plays update, overwrite not set');

                $skipWatchDates->add($localWatchDate, $localWatchDatePlays);
            }
        }

        foreach ($latestTraktWatchDateToPlaysMap->removeWatchDates($skipWatchDates) as $watchedAt => $plays) {
            $this->replacePlaysForMovieWatchDate(
                $movie,
                $userId,
                Date::createFromString($watchedAt),
                $plays,
            );
        }
    }

    private function isWatchedCacheUpToDate(int $userId, Api\Trakt\ValueObject\User\Movie\Watched\Dto $watchedMovie) : bool
    {
        $cacheLastUpdated = $this->traktApiCacheUserMovieWatchedService->findLastUpdatedByTraktId($userId, $watchedMovie->getMovie()->getTraktId());

        return $cacheLastUpdated !== null && $watchedMovie->getLastUpdated()->isEqual($cacheLastUpdated) === true;
    }

    private function removeWatchesNoLongerExistingInTrakt(int $userId, Api\Trakt\ValueObject\User\Movie\Watched\DtoList $traktWatchedMovies, bool $overwriteExistingData) : void
    {
        foreach ($this->traktApi->fetchUniqueCachedTraktIds($userId) as $cachedTraktId) {
            if ($traktWatchedMovies->containsTraktId($cachedTraktId) === true) {
                continue;
            }

            $this->traktApi->removeWatchCacheByTraktId($userId, $cachedTraktId);

            if ($overwriteExistingData === false) {
                continue;
            }

            $this->movieApi->deleteHistoryForUserByTraktId($userId, $cachedTraktId);
            $this->logger->info('Trakt history import: Removed outdated watch dates for movie with trakt id: ' . $cachedTraktId);
        }
    }

    private function replacePlaysForMovieWatchDate(MovieEntity $movie, int $userId, Date $watchedAt, int $plays) : void
    {
        $this->movieApi->replaceHistoryForMovieByDate($movie->getId(), $userId, $watchedAt, $plays);

        $this->logger->info('Trakt history import: Imported "' . $movie->getTitle() . "\" watch date $watchedAt  with \"$plays\" plays");
    }
}
