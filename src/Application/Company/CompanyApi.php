<?php declare(strict_types=1);

namespace Movary\Application\Company;

class CompanyApi
{
    public function __construct(
        private readonly CompanyRepository $repository,
    ) {
    }

    public function create(string $name, ?string $originCountry, int $tmdbId) : CompanyEntity
    {
        return $this->repository->create($name, $originCountry, $tmdbId);
    }

    public function deleteByTmdbId(int $tmdbId) : void
    {
        $this->repository->delete($tmdbId);
    }

    public function findByNameAndOriginCountry(string $name, ?string $originCountry) : ?CompanyEntity
    {
        return $this->repository->findByNameAndOriginCountry($name, $originCountry);
    }

    public function findByTmdbId(int $tmdbId) : ?CompanyEntity
    {
        return $this->repository->findByTmdbId($tmdbId);
    }

    public function update(int $id, string $name, ?string $originCountry) : CompanyEntity
    {
        return $this->repository->update($id, $name, $originCountry);
    }
}
