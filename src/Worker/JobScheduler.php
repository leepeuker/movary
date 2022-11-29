<?php declare(strict_types=1);

namespace Movary\Worker;

use Movary\ValueObject\JobStatus;
use Movary\ValueObject\JobType;

class JobScheduler
{
    private const IMAGE_CACHE_MOVIE_ID_BATCH_LIMIT = 200;

    public function __construct(
        private readonly Repository $repository,
        private array $scheduledMovieIdsForImageCacheJob = [],
    ) {
    }

    public function __destruct()
    {
        if (count($this->scheduledMovieIdsForImageCacheJob) === 0) {
            return;
        }

        $this->addTmdbImageCacheJob(array_keys($this->scheduledMovieIdsForImageCacheJob));
    }

    public function addImdbSyncJob(JobStatus $jobStatus) : void
    {
        $this->repository->addJob(JobType::createImdbSync(), $jobStatus);
    }

    public function addLetterboxdImportHistoryJob(int $userId, string $importFile) : void
    {
        $this->repository->addJob(JobType::createLetterboxdImportHistory(), JobStatus::createWaiting(), $userId, ['importFile' => $importFile]);
    }

    public function addLetterboxdImportRatingsJob(int $userId, string $importFile) : void
    {
        $this->repository->addJob(JobType::createLetterboxdImportRatings(), JobStatus::createWaiting(), $userId, ['importFile' => $importFile]);
    }

    public function addTmdbImageCacheJob(array $movieIds = []) : void
    {
        $this->repository->addJob(JobType::createTmdbImageCache(), JobStatus::createWaiting(), parameters: ['movieIds' => $movieIds]);
    }

    public function addTmdbSyncJob(JobStatus $jobStatus) : void
    {
        $this->repository->addJob(JobType::createTmdbSync(), $jobStatus);
    }

    public function addTraktImportHistoryJob(int $userId, ?JobStatus $jobStatus = null) : void
    {
        $this->repository->addJob(JobType::createTraktImportHistory(), $jobStatus ?? JobStatus::createWaiting(), $userId);
    }

    public function addTraktImportRatingsJob(int $userId, ?JobStatus $jobStatus = null) : void
    {
        $this->repository->addJob(JobType::createTraktImportRatings(), $jobStatus ?? JobStatus::createWaiting(), $userId);
    }

    public function storeMovieIdForTmdbImageCacheJob(int $movieId) : void
    {
        if (count($this->scheduledMovieIdsForImageCacheJob) >= self::IMAGE_CACHE_MOVIE_ID_BATCH_LIMIT) {
            $this->addTmdbImageCacheJob(array_keys($this->scheduledMovieIdsForImageCacheJob));
            $this->scheduledMovieIdsForImageCacheJob = [];
        }

        $this->scheduledMovieIdsForImageCacheJob[$movieId] = true;
    }
}
