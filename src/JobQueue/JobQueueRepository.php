<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Doctrine\DBAL\Connection;
use Movary\Util\Json;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Job;
use Movary\ValueObject\JobList;
use Movary\ValueObject\JobStatus;
use Movary\ValueObject\JobType;

class JobQueueRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function addJob(JobType $type, JobStatus $status, ?int $userId = null, ?array $parameters = null) : void
    {
        $this->dbConnection->insert(
            'job_queue',
            [
                'job_type' => $type,
                'job_status' => $status,
                'user_id' => $userId,
                'parameters' => $parameters !== null ? Json::encode($parameters) : null,
            ]
        );
    }

    public function fetchJobs(int $userId) : JobList
    {
        $data = $this->dbConnection->fetchAllAssociative('SELECT * FROM `job_queue` WHERE user_id = ? OR user_id IS NULL ORDER BY created_at DESC, id DESC LIMIT 30', [$userId]);

        return JobList::createFromArray($data);
    }

    public function fetchOldestWaitingJob() : ?Job
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `job_queue` WHERE job_status = ? ORDER BY `created_at` LIMIT 1', [JobStatus::createWaiting()]);

        if ($data === false) {
            return null;
        }

        return Job::createFromArray($data);
    }

    public function findLastDateForJobByType(JobType $jobType) : ?DateTime
    {
        $data = $this->dbConnection->fetchOne('SELECT created_at FROM `job_queue` WHERE job_type = ? AND job_status = ? ORDER BY created_at', [$jobType, JobStatus::createDone()]);

        if ($data === false) {
            return null;
        }

        return DateTime::createFromString($data);
    }

    public function findLastDateForJobByTypeAndUserId(JobType $jobType, int $userId) : ?DateTime
    {
        $data =
            $this->dbConnection->fetchOne(
                'SELECT created_at FROM `job_queue` WHERE job_type = ? AND job_status = ? AND user_id = ? ORDER BY created_at DESC',
                [
                    $jobType,
                    JobStatus::createDone(),
                    $userId,
                ]
            );

        if ($data === false) {
            return null;
        }

        return DateTime::createFromString($data);
    }

    public function updateJobStatus(int $id, JobStatus $status) : void
    {
        $this->dbConnection->update('job_queue', ['job_status' => (string)$status], ['id' => $id]);
    }
}
