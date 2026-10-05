<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Movary\ValueObject\JobStatus;

class JobQueueScheduler
{
    private const int IMAGE_CACHE_BATCH_LIMIT = 250;

    private const int TMDB_MOVIE_SYNC_BATCH_LIMIT = 250;

    private array $movieIdsStoredForTmdbSync = [];

    public function __construct(
        private readonly JobQueueApi $jobQueueApi,
        private readonly bool $enableImageCaching,
        private array $movieIdsForImageCacheJob = [],
        private array $personIdsForImageCacheJob = [],
        private array $movieIdsForTmdbSyncJob = [],
    ) {
    }

    public function __destruct()
    {
        if ($this->getCountOfIdsForImageCacheJob() > 0) {
            $this->addTmdbImageCacheJob();
        }

        if (count($this->movieIdsForTmdbSyncJob) > 0) {
            $this->addTmdbMovieSyncJob();
        }
    }

    public function storeMovieIdForTmdbImageCacheJob(int $movieId) : void
    {
        if ($this->getCountOfIdsForImageCacheJob() >= self::IMAGE_CACHE_BATCH_LIMIT) {
            $this->addTmdbImageCacheJob();
        }

        $this->movieIdsForImageCacheJob[$movieId] = true;
    }

    public function storePersonIdForTmdbImageCacheJob(int $personId) : void
    {
        if ($this->getCountOfIdsForImageCacheJob() >= self::IMAGE_CACHE_BATCH_LIMIT) {
            $this->addTmdbImageCacheJob();
        }

        $this->personIdsForImageCacheJob[$personId] = true;
    }

    public function storeMovieIdForTmdbSyncJob(int $movieId) : void
    {
        if (isset($this->movieIdsStoredForTmdbSync[$movieId]) === true) {
            return;
        }

        if (count($this->movieIdsForTmdbSyncJob) >= self::TMDB_MOVIE_SYNC_BATCH_LIMIT) {
            $this->addTmdbMovieSyncJob();
        }

        $this->movieIdsStoredForTmdbSync[$movieId] = true;
        $this->movieIdsForTmdbSyncJob[$movieId] = true;
    }

    private function addTmdbImageCacheJob() : void
    {
        if ($this->enableImageCaching === false) {
            return;
        }

        $this->jobQueueApi->addTmdbImageCacheJob(array_keys($this->movieIdsForImageCacheJob), array_keys($this->personIdsForImageCacheJob));

        $this->personIdsForImageCacheJob = [];
        $this->movieIdsForImageCacheJob = [];
    }

    private function addTmdbMovieSyncJob() : void
    {
        $this->jobQueueApi->addTmdbMovieSyncJob(
            JobStatus::createWaiting(),
            array_keys($this->movieIdsForTmdbSyncJob),
        );

        $this->movieIdsForTmdbSyncJob = [];
    }

    private function getCountOfIdsForImageCacheJob() : int
    {
        return count($this->movieIdsForImageCacheJob) + count($this->personIdsForImageCacheJob);
    }
}
