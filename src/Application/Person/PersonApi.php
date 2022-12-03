<?php declare(strict_types=1);

namespace Movary\Application\Person;

use Movary\ValueObject\Gender;

class PersonApi
{
    public function __construct(
        private readonly PersonRepository $repository,
    ) {
    }

    public function create(string $name, Gender $gender, ?string $knownForDepartment, int $tmdbId, ?string $tmdbPosterPath) : PersonEntity
    {
        return $this->repository->create($name, $gender, $knownForDepartment, $tmdbId, $tmdbPosterPath);
    }

    public function findById(int $personId) : ?PersonEntity
    {
        return $this->repository->findByPersonId($personId);
    }

    public function findByTmdbId(int $tmdbId) : ?PersonEntity
    {
        return $this->repository->findByTmdbId($tmdbId);
    }

    public function update(int $id, string $name, Gender $gender, ?string $knownForDepartment, int $tmdbId, ?string $tmdbPosterPath) : void
    {
        $this->repository->update($id, $name, $gender, $knownForDepartment, $tmdbId, $tmdbPosterPath);
    }
}
