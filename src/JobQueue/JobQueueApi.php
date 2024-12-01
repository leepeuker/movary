<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Movary\ValueObject\JobStatus;
use Movary\ValueObject\JobType;

class JobQueueApi
{
    public function __construct(
        private readonly JobQueueRepository $repository,
    ) {
    }

    public function addImdbSyncJob(JobStatus $jobStatus) : int
    {
        return $this->repository->addJob(JobType::createImdbSync(), $jobStatus);
    }

    public function addJellyfinExportMoviesJob(int $userId, array $movieIds = [], ?JobStatus $jobStatus = null) : int
    {
        return $this->repository->addJob(
            JobType::createJellyfinExportMovies(),
            $jobStatus ?? JobStatus::createWaiting(),
            $userId,
            parameters: [
                'movieIds' => $movieIds,
            ],
        );
    }

    public function addJellyfinImportMoviesJob(int $userId, ?JobStatus $jobStatus = null) : int
    {
        return $this->repository->addJob(
            JobType::createJellyfinImportMovies(),
            $jobStatus ?? JobStatus::createWaiting(),
            $userId,
        );
    }

    public function addLetterboxdImportHistoryJob(int $userId, string $importFile) : void
    {
        $this->repository->addJob(JobType::createLetterboxdImportHistory(), JobStatus::createWaiting(), $userId, ['importFile' => $importFile]);
    }

    public function addLetterboxdImportRatingsJob(int $userId, string $importFile) : void
    {
        $this->repository->addJob(JobType::createLetterboxdImportRatings(), JobStatus::createWaiting(), $userId, ['importFile' => $importFile]);
    }

    public function addPlexImportWatchlistJob(int $userId, ?JobStatus $jobStatus = null) : int
    {
        return $this->repository->addJob(JobType::createPlexImportWatchlist(), $jobStatus ?? JobStatus::createWaiting(), $userId);
    }

    public function addTmdbImageCacheJob(array $movieIds = [], array $personIds = [], ?JobStatus $jobStatus = null) : int
    {
        return $this->repository->addJob(
            JobType::createTmdbImageCache(),
            $jobStatus ?? JobStatus::createWaiting(),
            parameters: [
                'movieIds' => $movieIds,
                'personIds' => $personIds
            ],
        );
    }

    public function addTmdbMovieSyncJob(JobStatus $jobStatus) : int
    {
        return $this->repository->addJob(JobType::createTmdbMovieSync(), $jobStatus);
    }

    public function addTmdbPersonSyncJob(JobStatus $createDone) : int
    {
        return $this->repository->addJob(JobType::createTmdbPersonSyncJob(), $createDone);
    }

    public function addTraktImportHistoryJob(int $userId, ?JobStatus $jobStatus = null) : int
    {
        return $this->repository->addJob(JobType::createTraktImportHistory(), $jobStatus ?? JobStatus::createWaiting(), $userId);
    }

    public function addTraktImportRatingsJob(int $userId, ?JobStatus $jobStatus = null) : int
    {
        return $this->repository->addJob(JobType::createTraktImportRatings(), $jobStatus ?? JobStatus::createWaiting(), $userId);
    }

    public function fetchJobsForStatusPage(int $limit) : array
    {
        $jobs = $this->repository->fetchJobs($limit);

        $jobsData = [];
        foreach ($jobs as $job) {
            $jobsData[] = [
                'type' => $job['job_type'],
                'status' => $job['job_status'],
                'userName' => $job['name'],
                'updatedAt' => $job['updated_at'],
                'createdAt' => $job['created_at'],
            ];
        }

        return $jobsData;
    }

    public function fetchOldestWaitingJob() : ?JobEntity
    {
        return $this->repository->fetchOldestWaitingJob();
    }

    public function find(int $userId, JobType $jobType) : ?JobEntityList
    {
        return $this->repository->find($userId, $jobType);
    }

    public function purgeAllJobs() : void
    {
        $this->repository->purgeProcessedJobs();
        $this->repository->purgeNotProcessedJobs();
    }

    public function purgeProcessedJobs() : void
    {
        $this->repository->purgeProcessedJobs();
    }

    public function setJobToInProgress(int $id) : void
    {
        $this->repository->updateJobStatus($id, JobStatus::createInProgress());
    }

    public function updateJobStatus(int $id, JobStatus $status) : void
    {
        $this->repository->updateJobStatus($id, $status);
    }
}
