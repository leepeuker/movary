<?php declare(strict_types=1);

namespace Movary\Domain\Person;

use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Gender;
use Traversable;

class PersonApi
{
    public function __construct(
        private readonly PersonRepository $repository,
    ) {
    }

    public function create(
        int $tmdbId,
        string $name,
        Gender $gender,
        ?string $knownForDepartment,
        ?string $tmdbPosterPath,
        ?string $biography = null,
        ?Date $birthDate = null,
        ?Date $deathDate = null,
        ?string $placeOfBirth = null,
        ?DateTime $updatedAtTmdb = null,
        ?string $imdbId = null,
    ) : PersonEntity {
        return $this->repository->create(
            $tmdbId,
            $name,
            $gender,
            $knownForDepartment,
            $tmdbPosterPath,
            $biography,
            $birthDate,
            $deathDate,
            $placeOfBirth,
            $updatedAtTmdb,
            $imdbId,
        );
    }

    public function createOrUpdatePersonWithTmdbCreditsData(
        int $tmdbId,
        string $name,
        Gender $gender,
        ?string $knownForDepartment,
        ?string $posterPath,
    ) : PersonEntity {
        $person = $this->findByTmdbId($tmdbId);

        if ($person === null) {
            return $this->create(
                tmdbId: $tmdbId,
                name: $name,
                gender: $gender,
                knownForDepartment: $knownForDepartment,
                tmdbPosterPath: $posterPath,
            );
        }

        if ($person->getName() !== $name ||
            $person->getGender()->isEqual($gender) === false ||
            $person->getKnownForDepartment() !== $knownForDepartment ||
            $person->getTmdbPosterPath() !== $posterPath
        ) {
            $this->repository->updateWithTmdbCreditsData(
                $person->getId(),
                $name,
                $gender,
                $knownForDepartment,
                $posterPath,
            );
        }

        return $person;
    }

    public function deleteAllNotReferenced() : void
    {
        $this->repository->deleteAllNotReferenced();
    }

    public function deleteById(int $id) : void
    {
        $this->repository->deleteById($id);
    }

    public function fetchAllOrderedByLastUpdatedAtTmdbAsc(?int $limit = null, ?array $ids = null) : Traversable
    {
        return $this->repository->fetchAllOrderedByLastUpdatedAtTmdbAsc($limit, $ids);
    }

    public function findById(int $personId) : ?PersonEntity
    {
        return $this->repository->findByPersonId($personId);
    }

    public function findByTmdbId(int $tmdbId) : ?PersonEntity
    {
        return $this->repository->findByTmdbId($tmdbId);
    }

    public function update(
        int $id,
        int $tmdbId,
        string $name,
        Gender $gender,
        ?string $knownForDepartment,
        ?string $tmdbPosterPath,
        ?string $biography,
        ?Date $birthDate,
        ?Date $deathDate,
        ?string $placeOfBirth,
        ?DateTime $updatedAtTmdb,
        ?string $imdbId,
    ) : PersonEntity {
        return $this->repository->update(
            $id,
            $tmdbId,
            $name,
            $gender,
            $knownForDepartment,
            $tmdbPosterPath,
            $biography,
            $birthDate,
            $deathDate,
            $placeOfBirth,
            $updatedAtTmdb,
            $imdbId,
        );
    }

    public function updateHideInTopLists(int $userId, int $personId, bool $isHidden) : void
    {
        $this->repository->updateHideInTopLists($userId, $personId, $isHidden);
    }
}
