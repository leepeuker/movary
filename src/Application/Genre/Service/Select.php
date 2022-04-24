<?php declare(strict_types=1);

namespace Movary\Application\Genre\Service;

use Movary\Application\Genre\Entity;
use Movary\Application\Genre\Repository;

class Select
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        return $this->repository->findByTmdbId($tmdbId);
    }
}
