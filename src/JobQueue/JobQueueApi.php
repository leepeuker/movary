<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Movary\ValueObject\DateTime;
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

    public function addLetterboxdImportHistoryJob(int $userId, string $importFile) : void
    {
        $this->repository->addJob(JobType::createLetterboxdImportHistory(), JobStatus::createWaiting(), $userId, ['importFile' => $importFile]);
    }

    public function addLetterboxdImportRatingsJob(int $userId, string $importFile) : void
    {
        $this->repository->addJob(JobType::createLetterboxdImportRatings(), JobStatus::createWaiting(), $userId, ['importFile' => $importFile]);
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
        return $this->repository->addJob(JobType::addTmdbPersonSyncJob(), $createDone);
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

    public function findLastImdbSync() : ?DateTime
    {
        return $this->repository->findLastDateForJobByType(JobType::createImdbSync());
    }

    public function findLastLetterboxdImportsForUser(int $userId) : array
    {
        return $this->repository->findLastLetterboxdImportsForUser($userId);
    }

    public function findLastTmdbSync() : ?DateTime
    {
        $lastMovieSync = $this->repository->findLastDateForJobByType(JobType::createTmdbMovieSync());
        $lastPersonSync = $this->repository->findLastDateForJobByType(JobType::createTmdbPersonSync());

        if ($lastMovieSync === null) {
            return $lastPersonSync;
        }

        if ($lastPersonSync === null) {
            return $lastMovieSync;
        }

        return $lastMovieSync->isAfter($lastPersonSync) ? $lastMovieSync : $lastPersonSync;
    }

    public function findLastTraktImportsForUser(int $userId) : array
    {
        return $this->repository->findLastTraktImportsForUser($userId);
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
