<?php declare(strict_types=1);

namespace Movary\Application\Company\Service;

use Movary\Application\Company\Entity;
use Movary\Application\Company\Repository;

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
