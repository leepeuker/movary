<?php declare(strict_types=1);

namespace Movary\JobQueue;

class JobQueueScheduler
{
    private const IMAGE_CACHE_MOVIE_ID_BATCH_LIMIT = 200;

    public function __construct(
        private readonly JobQueueApi $jobQueueApi,
        private array $scheduledMovieIdsForImageCacheJob = [],
    ) {
    }

    public function __destruct()
    {
        if (count($this->scheduledMovieIdsForImageCacheJob) === 0) {
            return;
        }

        $this->jobQueueApi->addTmdbImageCacheJob(array_keys($this->scheduledMovieIdsForImageCacheJob));
    }

    public function storeMovieIdForTmdbImageCacheJob(int $movieId) : void
    {
        if (count($this->scheduledMovieIdsForImageCacheJob) >= self::IMAGE_CACHE_MOVIE_ID_BATCH_LIMIT) {
            $this->jobQueueApi->addTmdbImageCacheJob(array_keys($this->scheduledMovieIdsForImageCacheJob));
            $this->scheduledMovieIdsForImageCacheJob = [];
        }

        $this->scheduledMovieIdsForImageCacheJob[$movieId] = true;
    }
}
