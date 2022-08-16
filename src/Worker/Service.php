<?php declare(strict_types=1);

namespace Movary\Worker;

use Movary\Application\Service\Letterboxd;
use Movary\Application\Service\Tmdb\SyncMovies;
use Movary\Application\Service\Trakt;
use Movary\Application\User\Api;
use Movary\ValueObject\Job;
use Movary\ValueObject\JobStatus;
use Movary\ValueObject\JobType;

class Service
{
    public function __construct(
        private readonly Repository $repository,
        private readonly Trakt\ImportWatchedMovies $traktSyncWatchedMovies,
        private readonly Trakt\ImportRatings $traktSyncRatings,
        private readonly Letterboxd\ImportRatings $letterboxdImportRatings,
        private readonly Letterboxd\ImportHistory $letterboxdImportHistory,
        private readonly SyncMovies $tmdbSyncMovies,
        private readonly Api $userApi,
    ) {
    }

    public function addLetterboxdImportHistoryJob(int $userId, string $importFile) : void
    {
        $this->repository->addJob(JobType::createLetterboxdImportHistory(), JobStatus::createWaiting(), $userId, ['importFile' => $importFile]);
    }

    public function addLetterboxdImportRatingsJob(int $userId, string $importFile) : void
    {
        $this->repository->addJob(JobType::createLetterboxdImportRatings(), JobStatus::createWaiting(), $userId, ['importFile' => $importFile]);
    }

    public function addTmdbSyncJob(JobStatus $jobStatus) : void
    {
        $this->repository->addJob(JobType::createTmdbSync(), $jobStatus);
    }

    public function addTraktImportHistoryJob(int $userId) : void
    {
        $this->repository->addJob(JobType::createTraktImportHistory(), JobStatus::createWaiting(), $userId);
    }

    public function addTraktImportRatingsJob(int $userId) : void
    {
        $this->repository->addJob(JobType::createTraktImportRatings(), JobStatus::createWaiting(), $userId);
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

    public function processJob(Job $job) : void
    {
        match (true) {
            $job->getType()->isOfTypeLetterboxdImportRankings() => $this->letterboxdImportRatings->executeJob($job),
            $job->getType()->isOfTypeLetterboxdImportHistory() => $this->letterboxdImportHistory->executeJob($job),
            $job->getType()->isOfTypeTraktImportRatings() => $this->traktSyncRatings->executeJob($job),
            $job->getType()->isOfTypeTraktImportHistory() => $this->traktSyncWatchedMovies->executeJob($job),
            $job->getType()->isOfTypeTmdbSync() => $this->tmdbSyncMovies->syncMovies(),
            default => throw new \RuntimeException('Job type not supported: ' . $job->getType()),
        };
    }

    public function setJobToInProgress(int $id) : void
    {
        $this->repository->updateJobStatus($id, JobStatus::createInProgress());
    }
}
