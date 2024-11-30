<?php declare(strict_types=1);

namespace Movary\Service\Trakt;

use Movary\Api;
use Movary\Api\Trakt\TraktApi;
use Movary\Api\Trakt\ValueObject\TraktCredentials;
use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\Api\Trakt\ValueObject\User\Movie\Watched\Dto;
use Movary\Api\Trakt\ValueObject\User\Movie\Watched\DtoList;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\JobQueue\JobEntity;
use Movary\ValueObject\Date;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ImportWatchedMovies
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly TraktApi $traktApi,
        private readonly Api\Trakt\Cache\User\Movie\Watched\Service $traktApiWatchedMoviesCache,
        private readonly LoggerInterface $logger,
        private readonly PlaysPerDateFetcher $playsPerDateFetcher,
        private readonly MovieImporter $movieImporter,
        private readonly TraktCredentialsProvider $credentialsProvider,
    ) {
    }

    public function deleteMovieWatchDate(MovieEntity $movie, int $userId, Date $localWatchDate) : void
    {
        $this->movieApi->deleteHistoryByIdAndDate($movie->getId(), $userId, $localWatchDate);

        $this->logger->info('Trakt history import: Deleted "' . $movie->getTitle() . '" watch date "' . $localWatchDate . '" not exising in trakt');
    }

    public function execute(int $userId, bool $overwriteExistingData = false, bool $ignoreCache = false) : void
    {
        $traktCredentials = $this->credentialsProvider->fetchTraktCredentialsByUserId($userId);

        $traktWatchedMovies = $this->traktApi->fetchUserMoviesWatched($traktCredentials);
        foreach ($traktWatchedMovies as $traktWatchedMovie) {
            $movie = $this->movieImporter->importMovie($traktWatchedMovie->getMovie());

            if ($ignoreCache === false && $this->isTraktCacheUpToDate($userId, $traktWatchedMovie) === true) {
                $this->logger->debug('Trakt history import: Skipped "' . $movie->getTitle() . '" because trakt cache is up to date');

                continue;
            }

            $traktId = $traktWatchedMovie->getMovie()->getTraktId();

            $this->importMovieHistory($traktCredentials, $userId, $traktId, $movie, $overwriteExistingData);

            $this->traktApiWatchedMoviesCache->setLastUpdated($userId, $traktId, $traktWatchedMovie->getLastUpdated());
        }

        $this->cleanupCachedMoviesNoLongerExistingInTrakt($userId, $traktWatchedMovies, $overwriteExistingData);
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing userId');
        }

        $this->execute($userId);
    }

    private function cleanupCachedMoviesNoLongerExistingInTrakt(int $userId, DtoList $traktWatchedMovies, bool $overwriteExistingData) : void
    {
        foreach ($this->traktApi->fetchUniqueCachedTraktIds($userId) as $cachedTraktId) {
            if ($traktWatchedMovies->containsTraktId($cachedTraktId) === true) {
                continue;
            }

            $this->traktApi->removeWatchCacheByTraktId($userId, $cachedTraktId);

            if ($overwriteExistingData === false) {
                $this->logger->debug('Trakt history import: Skipped removing watch date(s) for movie no longer existing in trakt, no overwrite set', ['traktId' => $cachedTraktId]);

                return;
            }

            $this->movieApi->deleteHistoryForUserByTraktId($userId, $cachedTraktId);
            $this->logger->info('Trakt history import: Removed outdated watch date(s) for movie with trakt id: ' . $cachedTraktId);
        }
    }

    private function importMovieHistory(
        TraktCredentials $traktCredentials,
        int $userId,
        TraktId $traktId,
        MovieEntity $movie,
        bool $overwriteExistingData,
    ) : void {
        $traktMovieWatchDates = $this->playsPerDateFetcher->fetchTraktPlaysPerDate($traktCredentials, $traktId);
        $skipTraktWatchDates = WatchDateToPlaysMap::create();

        $localMovieWatchDates = $this->movieApi->fetchHistoryByMovieId($movie->getId(), $userId);

        foreach ($localMovieWatchDates as $localMovieWatchDate) {
            $localWatchDate = Date::createFromString($localMovieWatchDate['watched_at']);

            if ($traktMovieWatchDates->get($localWatchDate) === null) {
                if ($overwriteExistingData === false) {
                    $this->logger->debug('Trakt history import: Skipped deleting "' . $movie->getTitle() . '" watch date "' . $localWatchDate . '" not exising in trakt, overwrite not set');

                    continue;
                }

                $this->deleteMovieWatchDate($movie, $userId, $localWatchDate);

                continue;
            }

            $localWatchDatePlays = $localMovieWatchDate['plays'];
            $traktWatchDatePlays = $traktMovieWatchDates->getPlaysForDate($localWatchDate);

            if ($localWatchDatePlays === $traktWatchDatePlays) {
                $this->logger->debug('Trakt history import: Skipped "' . $movie->getTitle() . '" watch date "' . $localWatchDate . '" plays update, already up to date');

                $skipTraktWatchDates->set($localWatchDate, $localWatchDatePlays);

                continue;
            }

            if ($overwriteExistingData === false) {
                $this->logger->debug('Trakt history import: Skipped "' . $movie->getTitle() . '" watch date "' . $localWatchDate . '" plays update, overwrite not set');

                $skipTraktWatchDates->set($localWatchDate, $localWatchDatePlays);
            }
        }

        $traktMovieWatchDatesWithoutSkipDates = $traktMovieWatchDates->removeWatchDates($skipTraktWatchDates);

        foreach ($traktMovieWatchDatesWithoutSkipDates as $watchedAt => $plays) {
            $this->replaceMovieWatchDate(
                $movie,
                $userId,
                Date::createFromString((string)$watchedAt),
                $plays,
            );
        }
    }

    private function isTraktCacheUpToDate(int $userId, Dto $watchedMovie) : bool
    {
        $cacheLastUpdated = $this->traktApiWatchedMoviesCache->findLastUpdated($userId, $watchedMovie->getMovie()->getTraktId());

        return $cacheLastUpdated !== null && $watchedMovie->getLastUpdated()->isEqual($cacheLastUpdated) === true;
    }

    private function replaceMovieWatchDate(MovieEntity $movie, int $userId, Date $watchedAt, int $plays) : void
    {
        $this->movieApi->replaceHistoryForMovieByDate($movie->getId(), $userId, $watchedAt, $plays);

        $this->logger->info('Trakt history import: Imported "' . $movie->getTitle() . "\" watch date $watchedAt  with \"$plays\" plays");
    }
}
