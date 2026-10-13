<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Movary\Util\Json;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\JobScheduleCronExpression;
use Movary\ValueObject\JobType;

class JobScheduleApi
{
    public function __construct(private readonly JobScheduleRepository $repository)
    {
    }

    public function addTmdbSyncSchedule(
        JobType $jobType,
        JobScheduleCronExpression $cronExpression,
        TmdbSyncJobParameters $parameters,
        DateTime $nextRunAt,
        int $userId,
    ) : int {
        return $this->repository->add(
            $jobType,
            $cronExpression,
            [
                'ids' => $parameters->getIds(),
                'maxAgeInHours' => $parameters->getMaxAgeInHours(),
                'threshold' => $parameters->getThreshold(),
                'onlyNeverSynced' => $parameters->isOnlyNeverSynced(),
            ],
            $nextRunAt,
            $userId,
        );
    }

    public function delete(int $id) : bool
    {
        return $this->repository->delete($id);
    }

    public function fetchForStatusPage() : array
    {
        return array_map(static fn(array $schedule) : array => [
            'id' => (int)$schedule['id'],
            'type' => $schedule['job_type'],
            'cronExpression' => $schedule['cron_expression'],
            'userId' => $schedule['user_id'] === null ? null : (int)$schedule['user_id'],
            'userName' => $schedule['user_name'],
            'parameters' => Json::decode($schedule['parameters']),
            'enabled' => (bool)$schedule['enabled'],
            'nextRunAt' => $schedule['next_run_at'],
        ], $this->repository->fetchAll());
    }

    public function setEnabled(int $id, bool $enabled) : bool
    {
        return $this->repository->setEnabled($id, $enabled);
    }
}
