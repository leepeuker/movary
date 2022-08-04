<?php declare(strict_types=1);

namespace Movary\Application\Company;

class Api
{
    public function __construct(
        private readonly Repository $repository,
    ) {
    }

    public function create(string $name, ?string $originCountry, int $tmdbId) : Entity
    {
        return $this->repository->create($name, $originCountry, $tmdbId);
    }

    public function deleteByTmdbId(int $tmdbId) : void
    {
        $this->repository->delete($tmdbId);
    }

    public function findByNameAndOriginCountry(string $name, ?string $originCountry) : ?Entity
    {
        return $this->repository->findByNameAndOriginCountry($name, $originCountry);
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        return $this->repository->findByTmdbId($tmdbId);
    }

    public function update(int $id, string $name, ?string $originCountry) : Entity
    {
        return $this->repository->update($id, $name, $originCountry);
    }
}
