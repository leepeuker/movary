<?php declare(strict_types=1);

namespace Movary\Application\Person;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Gender;
use RuntimeException;

class Repository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(string $name, Gender $gender, ?string $knownForDepartment, int $tmdbId, ?string $posterPath) : Entity
    {
        $this->dbConnection->insert(
            'person',
            [
                'name' => $name,
                'gender' => $gender->asInt(),
                'known_for_department' => $knownForDepartment,
                'tmdb_id' => $tmdbId,
                'poster_path' => $posterPath,
            ]
        );

        return $this->fetchById((int)$this->dbConnection->lastInsertId());
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `person` WHERE tmdb_id = ?', [$tmdbId]);

        if ($data === false) {
            return null;
        }

        return Entity::createFromArray($data);
    }

    public function update(int $id, string $name, Gender $gender, ?string $knownForDepartment, int $tmdbId, ?string $posterPath) : void
    {
        $this->dbConnection->update(
            'person',
            [
                'name' => $name,
                'gender' => $gender->asInt(),
                'known_for_department' => $knownForDepartment,
                'tmdb_id' => $tmdbId,
                'poster_path' => $posterPath,
            ],
            [
                'id' => $id
            ]
        );
    }

    private function fetchById(int $id) : Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `person` WHERE id = ?', [$id]);

        if ($data === false) {
            throw new RuntimeException('No genre found by id: ' . $id);
        }

        return Entity::createFromArray($data);
    }
}
