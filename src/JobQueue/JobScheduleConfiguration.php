<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Movary\ValueObject\DateTime;
use Movary\ValueObject\JobScheduleCronExpression;

class JobScheduleConfiguration
{
    public function __construct(
        private readonly JobScheduleCronExpression $cronExpression,
        private readonly DateTime $nextRunAt,
    ) {
    }

    public function getCronExpression() : JobScheduleCronExpression
    {
        return $this->cronExpression;
    }

    public function getNextRunAt() : DateTime
    {
        return $this->nextRunAt;
    }
}
