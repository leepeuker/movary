<?php declare(strict_types=1);

namespace Movary\JobQueue;

use InvalidArgumentException;

class TmdbSyncJobParameters
{
    public const int MAX_THRESHOLD = 10000;

    private function __construct(
        private readonly ?int $maxAgeInHours,
        private readonly ?int $threshold,
        private readonly array $ids,
        private readonly bool $onlyNeverSynced,
    ) {
    }

    public static function create(
        ?int $maxAgeInHours,
        ?int $threshold,
        array $ids,
        bool $onlyNeverSynced,
    ) : self {
        if ($maxAgeInHours !== null && $maxAgeInHours < 1) {
            throw new InvalidArgumentException('Maximum age must be a positive integer.');
        }
        if ($threshold !== null && ($threshold < 1 || $threshold > self::MAX_THRESHOLD)) {
            throw new InvalidArgumentException('Maximum records must be between 1 and ' . self::MAX_THRESHOLD . '.');
        }
        foreach ($ids as $id) {
            if (is_int($id) === false || $id < 1) {
                throw new InvalidArgumentException('IDs must contain only positive integers.');
            }
        }

        return new self($maxAgeInHours, $threshold, array_values(array_unique($ids)), $onlyNeverSynced);
    }

    public function getIds() : array
    {
        return $this->ids;
    }

    public function getMaxAgeInHours() : ?int
    {
        return $this->maxAgeInHours;
    }

    public function getThreshold() : ?int
    {
        return $this->threshold;
    }

    public function isOnlyNeverSynced() : bool
    {
        return $this->onlyNeverSynced;
    }

    public function requiresUnboundedSyncConfirmation() : bool
    {
        return $this->ids === [] && $this->threshold === null;
    }
}
