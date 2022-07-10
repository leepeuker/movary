<?php declare(strict_types=1);

namespace Movary\Api\Trakt\Cache\User\Movie\Watched;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
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

    public function findLastUpdatedByTraktId(int $userId, TraktId $traktId) : ?DateTime
    {
        return $this->repository->findLastUpdatedByTraktId($userId, $traktId);
    }

    public function remove(int $userId, TraktId $traktId) : void
    {
        $this->repository->remove($userId, $traktId);
    }

    public function setOne(int $userId, TraktId $traktId, DateTime $lastUpdated) : void
    {
        $this->repository->set($userId, $traktId, $lastUpdated);
    }
}
