<?php declare(strict_types=1);

namespace Movary\Domain\Person;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Gender;
use RuntimeException;

class PersonRepository
{
    public function __construct(
        private readonly Connection $dbConnection,
        private readonly \PDO $pdo,
    ) {
    }

    public function create(
        int $tmdbId,
        string $name,
        Gender $gender,
        ?string $knownForDepartment,
        ?string $tmdbPosterPath,
        ?Date $birthDate = null,
        ?Date $deathDate = null,
        ?string $placeOfBirth = null,
        ?DateTime $updatedAtTmdb = null,
    ) : PersonEntity {
        $this->dbConnection->insert(
            'person',
            [
                'name' => $name,
                'gender' => $gender->asInt(),
                'known_for_department' => $knownForDepartment,
                'tmdb_id' => $tmdbId,
                'tmdb_poster_path' => $tmdbPosterPath,
                'birth_date' => $birthDate === null ? null : (string)$birthDate,
                'death_date' => $deathDate === null ? null : (string)$deathDate,
                'place_of_birth' => $placeOfBirth,
                'updated_at_tmdb' => $updatedAtTmdb === null ? null : (string)$updatedAtTmdb,
            ],
        );

        return $this->fetchById((int)$this->dbConnection->lastInsertId());
    }

    public function fetchAllOrderedByLastUpdatedAtTmdbAsc(?int $limit = null) : \Iterator
    {
        $query = 'SELECT * FROM `person` ORDER BY updated_at_tmdb ASC';

        if ($limit !== null) {
            $query .= ' LIMIT ' . $limit;
        }

        $statement = $this->pdo->prepare($query);
        $statement->execute();

        return $statement->getIterator();
    }

    public function findByPersonId(int $personId) : ?PersonEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `person` WHERE id = ?', [$personId]);

        if ($data === false) {
            return null;
        }

        return PersonEntity::createFromArray($data);
    }

    public function findByTmdbId(int $tmdbId) : ?PersonEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `person` WHERE tmdb_id = ?', [$tmdbId]);

        if ($data === false) {
            return null;
        }

        return PersonEntity::createFromArray($data);
    }

    public function update(
        int $id,
        int $tmdbId,
        string $name,
        Gender $gender,
        ?string $knownForDepartment,
        ?string $tmdbPosterPath,
        ?Date $birthDate = null,
        ?Date $deathDate = null,
        ?string $placeOfBirth = null,
        ?DateTime $updatedAtTmdb = null,
    ) : void {
        $this->dbConnection->update(
            'person',
            [
                'name' => $name,
                'gender' => $gender->asInt(),
                'known_for_department' => $knownForDepartment,
                'tmdb_id' => $tmdbId,
                'tmdb_poster_path' => $tmdbPosterPath,
                'birth_date' => $birthDate === null ? null : (string)$birthDate,
                'death_date' => $deathDate === null ? null : (string)$deathDate,
                'place_of_birth' => $placeOfBirth,
                'updated_at_tmdb' => $updatedAtTmdb === null ? null : (string)$updatedAtTmdb,
            ],
            [
                'id' => $id,
            ],
        );
    }

    private function fetchById(int $id) : PersonEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `person` WHERE id = ?', [$id]);

        if ($data === false) {
            throw new RuntimeException('No genre found by id: ' . $id);
        }

        return PersonEntity::createFromArray($data);
    }
}
