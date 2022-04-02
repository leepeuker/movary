<?php declare(strict_types=1);

namespace Movary\Application\Person\Service;

use Movary\Application\Person\Entity;
use Movary\Application\Person\Repository;

class Select
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        return $this->repository->findByTmdbId($tmdbId);
    }
}
