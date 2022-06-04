<?php declare(strict_types=1);

namespace Movary\Application\Genre;

class Api
{
    public function __construct(
        private readonly Repository $repository,
    ) {
    }

    public function create(string $name, int $tmdbId) : Entity
    {
        return $this->repository->create($name, $tmdbId);
    }

    public function findById(int $genreId) : ?Entity
    {
        return $this->repository->findById($genreId);
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        return $this->repository->findByTmdbId($tmdbId);
    }
}
