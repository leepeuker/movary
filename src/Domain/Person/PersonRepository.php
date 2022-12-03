<?php declare(strict_types=1);

namespace Movary\Domain\Person;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Gender;
use RuntimeException;

class PersonRepository
{
    public function __construct(
        private readonly Connection $dbConnection,
    ) {
    }

    public function create(string $name, Gender $gender, ?string $knownForDepartment, int $tmdbId, ?string $tmdbPosterPath) : PersonEntity
    {
        $this->dbConnection->insert(
            'person',
            [
                'name' => $name,
                'gender' => $gender->asInt(),
                'known_for_department' => $knownForDepartment,
                'tmdb_id' => $tmdbId,
                'tmdb_poster_path' => $tmdbPosterPath,
            ]
        );

        return $this->fetchById((int)$this->dbConnection->lastInsertId());
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

    public function update(int $id, string $name, Gender $gender, ?string $knownForDepartment, int $tmdbId, ?string $tmdbPosterPath) : void
    {
        $this->dbConnection->update(
            'person',
            [
                'name' => $name,
                'gender' => $gender->asInt(),
                'known_for_department' => $knownForDepartment,
                'tmdb_id' => $tmdbId,
                'tmdb_poster_path' => $tmdbPosterPath,
            ],
            [
                'id' => $id,
            ]
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
