<?php declare(strict_types=1);

namespace Movary\JobQueue;

use Movary\ValueObject\JobType;

class JobScheduleFilter
{
    private function __construct(
        private readonly ?int $userId,
        private readonly bool $withoutUser,
        private readonly ?JobType $type,
        private readonly ?bool $enabled,
    ) {
    }

    public static function create(
        ?int $userId = null,
        bool $withoutUser = false,
        ?JobType $type = null,
        ?bool $enabled = null,
    ) : self {
        return new self($userId, $withoutUser, $type, $enabled);
    }

    public function getEnabled() : ?bool
    {
        return $this->enabled;
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
        return $this->userId !== null || $this->withoutUser || $this->type !== null || $this->enabled !== null;
    }

    public function isWithoutUser() : bool
    {
        return $this->withoutUser;
    }
}
