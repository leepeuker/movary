<?php declare(strict_types=1);

namespace Movary\Api\Trakt\Cache\User\Movie\Watched;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Api\Trakt\ValueObject\User\Movie\Watched\DtoList;
use Movary\ValueObject\DateTime;

class Service
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function fetchAllUniqueTraktIds() : array
    {
        return $this->repository->fetchAllUniqueTraktIds();
    }

    public function findLastUpdatedByTraktId(TraktId $traktId) : ?DateTime
    {
        return $this->repository->findLastUpdatedByTraktId($traktId);
    }

    public function remove(TraktId $traktId) : void
    {
        $this->repository->remove($traktId);
    }

    public function setOne(TraktId $traktId, DateTime $lastUpdated) : void
    {
        $this->repository->set($traktId, $lastUpdated);
    }
}
