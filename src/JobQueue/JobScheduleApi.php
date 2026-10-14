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

    public function addImdbSyncSchedule(
        JobScheduleCronExpression $cronExpression,
        TmdbSyncJobParameters $parameters,
        DateTime $nextRunAt,
        int $userId,
    ) : int {
        return $this->repository->add(
            JobType::createImdbSync(),
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

    public function addTmdbImageCacheRefreshSchedule(
        JobScheduleCronExpression $cronExpression,
        TmdbImageCacheJobParameters $parameters,
        DateTime $nextRunAt,
        int $userId,
    ) : int {
        return $this->repository->add(
            JobType::createTmdbImageCache(),
            $cronExpression,
            [
                'movieIds' => $parameters->getMovieIds(),
                'personIds' => $parameters->getPersonIds(),
            ],
            $nextRunAt,
            $userId,
        );
    }

    public function delete(int $id) : bool
    {
        return $this->repository->delete($id);
    }

    public function deleteAll() : void
    {
        $this->repository->deleteAll();
    }

    public function update(
        int $id,
        JobType $jobType,
        JobScheduleConfiguration $configuration,
        array $parameters,
        bool $enabled,
    ) : bool {
        return $this->repository->update(
            $id,
            $jobType,
            $configuration->getCronExpression(),
            $parameters,
            $enabled,
            $configuration->getNextRunAt(),
        );
    }

    public function countSchedules(?JobScheduleFilter $filter = null) : int
    {
        return $this->repository->countSchedules($filter);
    }

    public function fetchForStatusPage(int $limit, int $offset = 0, ?JobScheduleFilter $filter = null) : array
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
        ], $this->repository->fetchSchedules($limit, $offset, $filter));
    }
}
