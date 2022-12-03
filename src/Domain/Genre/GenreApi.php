<?php declare(strict_types=1);

namespace Movary\Domain\Genre;

class GenreApi
{
    public function __construct(
        private readonly GenreRepository $repository,
    ) {
    }

    public function create(string $name, int $tmdbId) : GenreEntity
    {
        return $this->repository->create($name, $tmdbId);
    }

    public function findById(int $genreId) : ?GenreEntity
    {
        return $this->repository->findById($genreId);
    }

    public function findByTmdbId(int $tmdbId) : ?GenreEntity
    {
        return $this->repository->findByTmdbId($tmdbId);
    }
}
