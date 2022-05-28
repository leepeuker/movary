<?php declare(strict_types=1);

namespace Movary\Application\Movie\Service;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Movie\Entity;
use Movary\Application\Movie\EntityList;
use Movary\Application\Movie\Repository;

class Select
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function fetchAll() : EntityList
    {
        return $this->repository->fetchAll();
    }

    public function fetchAllOrderedByLastUpdatedAtTmdbDesc() : EntityList
    {
        return $this->repository->fetchAllOrderedByLastUpdatedAtTmdbDesc();
    }

    public function findById(int $movieId) : ?Entity
    {
        return $this->repository->findById($movieId);
    }

    public function findByLetterboxdId(string $letterboxdId) : ?Entity
    {
        return $this->repository->findByLetterboxdId($letterboxdId);
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        return $this->repository->findByTmdbId($tmdbId);
    }

    public function findByTraktId(TraktId $traktId) : ?Entity
    {
        return $this->repository->findByTraktId($traktId);
    }
}
