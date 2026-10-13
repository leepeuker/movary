<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Movary\ValueObject\DateTime;
use Movary\ValueObject\JobScheduleFrequency;

class JobScheduleConfiguration
{
    public function __construct(
        private readonly JobScheduleFrequency $frequency,
        private readonly DateTime $firstRunAt,
    ) {
    }

    public function getFirstRunAt() : DateTime
    {
        return $this->firstRunAt;
    }

    public function getFrequency() : JobScheduleFrequency
    {
        return $this->frequency;
    }
}
