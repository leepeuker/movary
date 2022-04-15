<?php declare(strict_types=1);

namespace Movary\Api\Trakt\Cache\User\Movie\Watched;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Api\Trakt\ValueObject\User\Movie\Watched\DtoList;
use Movary\ValueObject\DateTime;

class Service
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function findLastUpdatedByTraktId(TraktId $traktId) : ?DateTime
    {
        return $this->repository->findLastUpdatedByTraktId($traktId);
    }

    public function findLatestLastUpdatedAt() : ?DateTime
    {
        return $this->repository->findLatestLastUpdatedAt();
    }

    public function removeMissingMoviesFromCache(DtoList $watchedMovies) : void
    {
        foreach ($this->repository->fetchAllUniqueTraktIds() as $traktId) {
            if ($watchedMovies->containsTraktId($traktId) === false) {
                $this->repository->removeAllWithTraktId($traktId);
            }
        }
    }

    public function setOne(TraktId $traktId, DateTime $lastUpdated) : void
    {
        $this->repository->create($traktId, $lastUpdated);
    }
}
