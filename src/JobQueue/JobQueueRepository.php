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

    public function countJobs(?JobQueueFilter $filter = null) : int
    {
        if ($filter === null) {
            return (int)$this->dbConnection->fetchOne('SELECT COUNT(*) FROM job_queue');
        }

        [$whereQuery, $parameters] = $this->createStatusPageFilter($filter);

        return (int)$this->dbConnection->fetchOne("SELECT COUNT(*) FROM job_queue jobs$whereQuery", $parameters);
    }

    public function fetchJobs(int $limit, int $offset = 0, ?JobQueueFilter $filter = null) : array
    {
        [$whereQuery, $parameters] = $this->createStatusPageFilter($filter ?? JobQueueFilter::create());

        return $this->dbConnection->fetchAllAssociative(
            "SELECT jobs.id, jobs.job_type, jobs.job_status, jobs.user_id, users.name, jobs.parameters,
                jobs.updated_at, jobs.created_at
            FROM job_queue jobs
            LEFT JOIN user users on jobs.user_id = users.id
            $whereQuery
            ORDER BY jobs.created_at DESC, jobs.id DESC 
            LIMIT $limit OFFSET $offset",
            $parameters,
        );
    }

    /**
     * @return array{string, list<int|string>}
     */
    private function createStatusPageFilter(JobQueueFilter $filter) : array
    {
        $conditions = [];
        $parameters = [];
        $userId = $filter->getUserId();

        if ($filter->isWithoutUser() === true) {
            $conditions[] = 'jobs.user_id IS NULL';
        } elseif ($userId !== null) {
            $conditions[] = 'jobs.user_id = ?';
            $parameters[] = $userId;
        }

        if ($filter->getType() !== null) {
            $conditions[] = 'jobs.job_type = ?';
            $parameters[] = (string)$filter->getType();
        }

        if ($filter->getStatus() !== null) {
            $conditions[] = 'jobs.job_status = ?';
            $parameters[] = (string)$filter->getStatus();
        }

        return [count($conditions) === 0 ? '' : ' WHERE ' . implode(' AND ', $conditions), $parameters];
    }

    public function deleteJob(int $id) : bool
    {
        return $this->dbConnection->delete('job_queue', ['id' => $id]) === 1;
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
