<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Movary\Util\Json;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\JobScheduleFrequency;
use Movary\ValueObject\JobType;

class JobScheduleEntity
{
    private function __construct(
        private readonly int $id,
        private readonly JobType $jobType,
        private readonly JobScheduleFrequency $frequency,
        private readonly ?int $userId,
        private readonly array $parameters,
        private readonly bool $enabled,
        private readonly DateTime $nextRunAt,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['id'],
            JobType::createFromString($data['job_type']),
            JobScheduleFrequency::createFromString($data['frequency']),
            $data['user_id'] === null ? null : (int)$data['user_id'],
            Json::decode($data['parameters']),
            (bool)$data['enabled'],
            DateTime::createFromString($data['next_run_at']),
        );
    }

    public function getFrequency() : JobScheduleFrequency
    {
        return $this->frequency;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getJobType() : JobType
    {
        return $this->jobType;
    }

    public function getNextRunAt() : DateTime
    {
        return $this->nextRunAt;
    }

    public function getParameters() : array
    {
        return $this->parameters;
    }

    public function getUserId() : ?int
    {
        return $this->userId;
    }

    public function isEnabled() : bool
    {
        return $this->enabled;
    }
}
