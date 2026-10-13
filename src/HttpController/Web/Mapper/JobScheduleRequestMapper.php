<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Mapper;

use InvalidArgumentException;
use Movary\JobQueue\JobScheduleConfiguration;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\JobScheduleFrequency;
use RuntimeException;

class JobScheduleRequestMapper
{
    public function map(Request $request) : ?JobScheduleConfiguration
    {
        $parameters = $request->getPostParameters();
        $frequency = $parameters['frequency'] ?? 'once';
        if ($frequency === 'once') {
            return null;
        }
        if (is_string($frequency) === false) {
            throw new InvalidArgumentException('Frequency is invalid.');
        }

        try {
            $scheduleFrequency = JobScheduleFrequency::createFromString($frequency);
        } catch (RuntimeException) {
            throw new InvalidArgumentException('Frequency is invalid.');
        }

        $firstRunAt = $parameters['firstRunAt'] ?? null;
        if (is_string($firstRunAt) === false || preg_match('/^[1-9][0-9]*$/D', $firstRunAt) !== 1) {
            throw new InvalidArgumentException('First run time is invalid.');
        }

        $timestamp = filter_var($firstRunAt, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($timestamp === false) {
            throw new InvalidArgumentException('First run time is invalid.');
        }

        $firstRunDateTime = DateTime::createFromUnixTimestamp($timestamp);
        if ($firstRunDateTime->isAfter(DateTime::create()) === false) {
            throw new InvalidArgumentException('First run time must be in the future.');
        }

        return new JobScheduleConfiguration($scheduleFrequency, $firstRunDateTime);
    }
}
