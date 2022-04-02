<?php declare(strict_types=1);

namespace Movary\Application\Person;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Gender;
use RuntimeException;

class Repository
{
    private Connection $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    public function create(string $name, Gender $gender, ?float $popularity, ?string $knownForDepartment, int $tmdbId) : Entity
    {
        $this->dbConnection->insert(
            'person',
            [
                'name' => $name,
                'gender' => $gender->asInt(),
                'popularity' => $popularity,
                'known_for_department' => $knownForDepartment,
                'tmdb_id' => $tmdbId,
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

    private function fetchById(int $id) : Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `person` WHERE id = ?', [$id]);

        if ($data === false) {
            throw new RuntimeException('No genre found by id: ' . $id);
        }

        return Entity::createFromArray($data);
    }
}
