<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Mapper;

use Movary\JobQueue\JobQueueFilter;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\JobStatus;
use Movary\ValueObject\JobType;
use RuntimeException;

class JobQueueFilterRequestMapper
{
    public function map(Request $request) : JobQueueFilter
    {
        $parameters = $request->getGetParameters();
        $user = $parameters['user'] ?? null;

        return JobQueueFilter::create(
            $this->mapUserId($user),
            $user === 'none',
            $this->mapJobType($parameters['type'] ?? null),
            $this->mapJobStatus($parameters['status'] ?? null),
        );
    }

    private function mapJobStatus(mixed $status) : ?JobStatus
    {
        if (is_string($status) === false || $status === '') {
            return null;
        }

        try {
            return JobStatus::createFromString($status);
        } catch (RuntimeException) {
            return null;
        }
    }

    private function mapJobType(mixed $type) : ?JobType
    {
        if (is_string($type) === false || $type === '') {
            return null;
        }

        try {
            return JobType::createFromString($type);
        } catch (RuntimeException) {
            return null;
        }
    }

    private function mapUserId(mixed $user) : ?int
    {
        if (is_string($user) === false || ctype_digit($user) === false || (int)$user < 1) {
            return null;
        }

        return (int)$user;
    }
}
