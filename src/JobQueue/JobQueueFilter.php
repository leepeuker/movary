<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Movary\ValueObject\JobStatus;
use Movary\ValueObject\JobType;

class JobQueueFilter
{
    private function __construct(
        private readonly ?int $userId,
        private readonly bool $withoutUser,
        private readonly ?JobType $type,
        private readonly ?JobStatus $status,
    ) {
    }

    public static function create(
        ?int $userId = null,
        bool $withoutUser = false,
        ?JobType $type = null,
        ?JobStatus $status = null,
    ) : self {
        return new self($userId, $withoutUser, $type, $status);
    }

    public function getStatus() : ?JobStatus
    {
        return $this->status;
    }

    public function getType() : ?JobType
    {
        return $this->type;
    }

    public function getUserId() : ?int
    {
        return $this->userId;
    }

    public function hasFilters() : bool
    {
        return $this->userId !== null || $this->withoutUser || $this->type !== null || $this->status !== null;
    }

    public function isWithoutUser() : bool
    {
        return $this->withoutUser;
    }
}
