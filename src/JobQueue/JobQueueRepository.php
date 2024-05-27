<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Doctrine\DBAL\Connection;
use Movary\Util\Json;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\JobStatus;
use Movary\ValueObject\JobType;
use RuntimeException;

class JobQueueRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function addJob(JobType $type, JobStatus $status, ?int $userId = null, ?array $parameters = null) : int
    {
        $this->dbConnection->insert(
            'job_queue',
            [
                'job_type' => $type,
                'job_status' => $status,
                'user_id' => $userId,
                'parameters' => $parameters !== null ? Json::encode($parameters) : null,
                'created_at' => (string)DateTime::create(),
            ],
        );

        $lastInsertId = $this->dbConnection->lastInsertId();
        if ($lastInsertId === false) {
            throw new RuntimeException('Could not get last inserted id.');
        }

        return (int)$lastInsertId;
    }

    public function fetchJobs(int $limit) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            "SELECT jobs.job_type, users.name, jobs.job_status, jobs.updated_at, jobs.created_at
            FROM job_queue jobs
            LEFT JOIN user users on jobs.user_id = users.id
            ORDER BY jobs.created_at DESC, jobs.id DESC 
            LIMIT $limit",
        );
    }

    public function fetchOldestWaitingJob() : ?JobEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `job_queue` WHERE job_status = ? ORDER BY `created_at` LIMIT 1', [JobStatus::createWaiting()]);

        if ($data === false) {
            return null;
        }

        return JobEntity::createFromArray($data);
    }

    public function find(int $userId, JobType $jobType) : ?JobEntityList
    {
        $data = $this->dbConnection->fetchAllAssociative(
            'SELECT * FROM `job_queue` WHERE job_type = ? and user_id = ? ORDER BY `created_at` DESC LIMIT 10',
            [
                $jobType,
                $userId
            ],
        );

        return JobEntityList::createFromArray($data);
    }

    public function purgeNotProcessedJobs() : void
    {
        $this->dbConnection->delete('job_queue', ['job_status' => (string)JobStatus::createWaiting()]);
        $this->dbConnection->delete('job_queue', ['job_status' => (string)JobStatus::createInProgress()]);
    }

    public function purgeProcessedJobs() : void
    {
        $this->dbConnection->delete('job_queue', ['job_status' => (string)JobStatus::createDone()]);
        $this->dbConnection->delete('job_queue', ['job_status' => (string)JobStatus::createFailed()]);
    }

    public function updateJobStatus(int $id, JobStatus $status) : void
    {
        $this->dbConnection->update(
            'job_queue',
            [
                'job_status' => (string)$status,
                'updated_at' => (string)DateTime::create(),
            ],
            [
                'id' => $id
            ],
        );
    }
}
