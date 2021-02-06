<?php declare(strict_types=1);

namespace Movary\Application\Genre\Service;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Genre\Entity;
use Movary\Application\Genre\Repository;

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
