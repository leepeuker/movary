<?php declare(strict_types=1);

namespace Movary\Application\Person\Service;

use Movary\Application\Person\Entity;
use Movary\Application\Person\Repository;

class Select
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function findById(int $personId) : ?Entity
    {
        return $this->repository->findByPersonId($personId);
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        return $this->repository->findByTmdbId($tmdbId);
    }

    public function findWatchedMoviesActedBy(int $personId) : array
    {
        return $this->repository->findWatchedMoviesActedBy($personId);
    }

    public function findWatchedMoviesDirectedBy(int $personId) : array
    {
        return $this->repository->findWatchedMoviesDirectedBy($personId);
    }
}
