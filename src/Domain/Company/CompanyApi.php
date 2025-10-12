<?php declare(strict_types=1);

namespace Movary\Domain\Company;

class CompanyApi
{
    public function __construct(
        private readonly CompanyRepository $repository,
    ) {
    }

    public function create(string $name, ?string $originCountry, int $tmdbId) : CompanyEntity
    {
        return CompanyEntity::createFromArray(
            $this->repository->create($name, $originCountry, $tmdbId),
        );
    }

    public function deleteByTmdbId(int $tmdbId) : void
    {
        $this->repository->delete($tmdbId);
    }

    public function findByNameAndOriginCountry(string $name, ?string $originCountry) : ?CompanyEntity
    {
        $data = $this->repository->findByNameAndOriginCountry($name, $originCountry);

        return $data === null ? null : CompanyEntity::createFromArray($data);
    }

    public function findByTmdbId(int $tmdbId) : ?CompanyEntity
    {
        $data = $this->repository->findByTmdbId($tmdbId);

        return $data === null ? null : CompanyEntity::createFromArray($data);
    }

    public function update(int $id, int $tmdbId, string $name, ?string $originCountry) : CompanyEntity
    {
        return CompanyEntity::createFromArray(
            $this->repository->update($id, $tmdbId, $name, $originCountry),
        );
    }
}
