<?php declare(strict_types=1);

namespace Movary\Application\Company\Service;

use Movary\Application\Company\Entity;
use Movary\Application\Company\Repository;

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
