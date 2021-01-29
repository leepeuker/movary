<?php declare(strict_types=1);

namespace Movary\Api\Trakt\Cache\User\Movie\Watched;

use Movary\Api\Trakt\ValueObject\User\Movie\Watched\Dto;
use Movary\Api\Trakt\ValueObject\User\Movie\Watched\DtoList;
use Movary\ValueObject\DateTime;

class Service
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function findLatestLastUpdatedAt() : ?DateTime
    {
        return $this->repository->findLatestLastUpdatedAt();
    }

    public function set(DtoList $moviesWatched) : void
    {
        $this->repository->clearCache();

        /** @var Dto $movieWatched */
        foreach ($moviesWatched as $movieWatched) {
            $this->repository->create($movieWatched->getMovie()->getTraktId(), $movieWatched->getLastUpdated());
        }
    }
}
