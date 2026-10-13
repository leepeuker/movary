<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Doctrine\DBAL\Connection;
use Movary\Util\Json;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\JobScheduleCronExpression;
use Movary\ValueObject\JobType;
use RuntimeException;

class JobScheduleRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function add(
        JobType $jobType,
        JobScheduleCronExpression $cronExpression,
        array $parameters,
        DateTime $nextRunAt,
        int $userId,
    ) : int {
        $this->dbConnection->insert('job_schedule', [
            'job_type' => (string)$jobType,
            'cron_expression' => (string)$cronExpression,
            'user_id' => $userId,
            'parameters' => Json::encode($parameters),
            'enabled' => 1,
            'next_run_at' => (string)$nextRunAt,
            'created_at' => (string)DateTime::create(),
        ]);

        $id = $this->dbConnection->lastInsertId();
        if ($id === false) {
            throw new RuntimeException('Could not get last inserted job schedule id.');
        }

        return (int)$id;
    }

    public function delete(int $id) : bool
    {
        return $this->dbConnection->delete('job_schedule', ['id' => $id]) === 1;
    }

    /** @return list<array<string, mixed>> */
    public function fetchAll() : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT schedules.*, users.name AS user_name
            FROM job_schedule schedules
            LEFT JOIN user users ON schedules.user_id = users.id
            ORDER BY schedules.id',
        );
    }

    /** @return list<JobScheduleEntity> */
    public function fetchDue(DateTime $currentDateTime) : array
    {
        $rows = $this->dbConnection->fetchAllAssociative(
            'SELECT * FROM job_schedule WHERE enabled = ? AND next_run_at <= ? ORDER BY next_run_at, id',
            [1, (string)$currentDateTime],
        );

        return array_map(JobScheduleEntity::createFromArray(...), $rows);
    }

    public function setEnabled(int $id, bool $enabled) : bool
    {
        return $this->dbConnection->update(
            'job_schedule',
            ['enabled' => $enabled ? 1 : 0, 'updated_at' => (string)DateTime::create()],
            ['id' => $id],
        ) === 1;
    }

    public function updateNextRunIfCurrent(
        int $id,
        DateTime $currentNextRun,
        DateTime $nextRun,
        DateTime $updatedAt,
    ) : bool {
        return $this->dbConnection->executeStatement(
            'UPDATE job_schedule SET next_run_at = ?, updated_at = ? '
            . 'WHERE id = ? AND enabled = ? AND next_run_at = ?',
            [(string)$nextRun, (string)$updatedAt, $id, 1, (string)$currentNextRun],
        ) === 1;
    }
}
