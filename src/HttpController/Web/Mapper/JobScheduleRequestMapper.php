<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Mapper;

use InvalidArgumentException;
use Movary\JobQueue\JobScheduleConfiguration;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\JobScheduleCronExpression;

class JobScheduleRequestMapper
{
    public function map(Request $request) : ?JobScheduleConfiguration
    {
        $parameters = $request->getPostParameters();
        $scheduleMode = $parameters['scheduleMode'] ?? 'once';
        if ($scheduleMode === 'once') {
            return null;
        }
        if ($scheduleMode !== 'recurring') {
            throw new InvalidArgumentException('Schedule mode is invalid.');
        }

        $expression = $parameters['cronExpression'] ?? null;
        if (is_string($expression) === false) {
            throw new InvalidArgumentException('Cron expression is invalid.');
        }

        try {
            $cronExpression = JobScheduleCronExpression::createFromString($expression);
            $nextRunAt = $cronExpression->calculateNextRun(DateTime::create());
        } catch (\Exception) {
            throw new InvalidArgumentException('Cron expression is invalid.');
        }

        return new JobScheduleConfiguration($cronExpression, $nextRunAt);
    }
}
