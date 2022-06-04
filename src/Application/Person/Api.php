<?php declare(strict_types=1);

namespace Movary\Application\Person;

use Movary\ValueObject\Gender;

class Api
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function create(string $name, Gender $gender, ?string $knownForDepartment, int $tmdbId, ?string $posterPath) : Entity
    {
        return $this->repository->create($name, $gender, $knownForDepartment, $tmdbId, $posterPath);
    }

    public function findById(int $personId) : ?Entity
    {
        return $this->repository->findByPersonId($personId);
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        return $this->repository->findByTmdbId($tmdbId);
    }

    public function update(int $id, string $name, Gender $gender, ?string $knownForDepartment, int $tmdbId, ?string $posterPath) : void
    {
        $this->repository->update($id, $name, $gender, $knownForDepartment, $tmdbId, $posterPath);
    }
}
