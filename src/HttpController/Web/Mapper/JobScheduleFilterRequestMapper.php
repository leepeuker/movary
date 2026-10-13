<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Mapper;

use Movary\JobQueue\JobScheduleFilter;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\JobType;
use RuntimeException;

class JobScheduleFilterRequestMapper
{
    public function map(Request $request) : JobScheduleFilter
    {
        $parameters = $request->getGetParameters();
        $user = $parameters['user'] ?? null;

        return JobScheduleFilter::create(
            $this->mapUserId($user),
            $user === 'none',
            $this->mapJobType($parameters['type'] ?? null),
            $this->mapEnabled($parameters['status'] ?? null),
        );
    }

    private function mapEnabled(mixed $status) : ?bool
    {
        return match ($status) {
            'enabled' => true,
            'disabled' => false,
            default => null,
        };
    }

    private function mapJobType(mixed $type) : ?JobType
    {
        if (is_string($type) === false || $type === '') {
            return null;
        }

        try {
            $jobType = JobType::createFromString($type);
        } catch (RuntimeException) {
            return null;
        }

        return $jobType->isOfTypeTmdbMovieSync()
            || $jobType->isOfTypeTmdbPersonSync()
            || $jobType->isOfTypeTmdbImageCache()
            || $jobType->isOfTypeImdbSync()
            ? $jobType
            : null;
    }

    private function mapUserId(mixed $user) : ?int
    {
        if (is_string($user) === false || ctype_digit($user) === false || (int)$user < 1) {
            return null;
        }

        return (int)$user;
    }
}
