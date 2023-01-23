<?php declare(strict_types=1);

namespace Movary\Api\Trakt\Cache\User\Movie\Watched;

use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\ValueObject\DateTime;

class Service
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function fetchAllUniqueTraktIds(int $userId) : array
    {
        return $this->repository->fetchAllUniqueTraktIds($userId);
    }

    public function findLastUpdated(int $userId, TraktId $traktId) : ?DateTime
    {
        return $this->repository->findLastUpdatedByTraktId($userId, $traktId);
    }

    public function removeLastUpdated(int $userId, TraktId $traktId) : void
    {
        $this->repository->remove($userId, $traktId);
    }

    public function setLastUpdated(int $userId, TraktId $traktId, DateTime $lastUpdated) : void
    {
        $this->repository->set($userId, $traktId, $lastUpdated);
    }
}
