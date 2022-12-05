<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Movary\Domain\User\UserApi;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Job;
use Movary\ValueObject\JobStatus;
use Movary\ValueObject\JobType;

class JobQueueApi
{
    public function __construct(
        private readonly JobQueueRepository $repository,
        private readonly UserApi $userApi,
    ) {
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

    public function fetchJobsForStatusPage(int $userId) : array
    {
        $jobs = $this->repository->fetchJobs($userId);

        $jobsData = [];
        foreach ($jobs as $job) {
            $jobUserId = $job->getUserId();

            $userName = $jobUserId === null ? null : $this->userApi->fetchUser($jobUserId)->getName();

            $jobsData[] = [
                'id' => $job->getId(),
                'type' => $job->getType(),
                'status' => $job->getStatus(),
                'userName' => $userName,
                'updatedAt' => $job->getUpdatedAt(),
                'createdAt' => $job->getCreatedAt(),
            ];
        }

        return $jobsData;
    }

    public function fetchOldestWaitingJob() : ?Job
    {
        return $this->repository->fetchOldestWaitingJob();
    }

    public function findLastImdbSync() : ?DateTime
    {
        return $this->repository->findLastDateForJobByType(JobType::createImdbSync());
    }

    public function findLastTmdbSync() : ?DateTime
    {
        return $this->repository->findLastDateForJobByType(JobType::createTmdbSync());
    }

    public function findLastTraktSync(int $userId) : ?DateTime
    {
        $ratingsDate = $this->repository->findLastDateForJobByTypeAndUserId(JobType::createTraktImportRatings(), $userId);
        $historyDate = $this->repository->findLastDateForJobByTypeAndUserId(JobType::createTraktImportHistory(), $userId);

        if ($ratingsDate > $historyDate) {
            return $ratingsDate;
        }

        return $historyDate;
    }

    public function purgeHistory() : void
    {
        $this->repository->purgeHistory();
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
