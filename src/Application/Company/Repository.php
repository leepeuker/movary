<?php declare(strict_types=1);

namespace Movary\Application\Company;

use Doctrine\DBAL\Connection;
use RuntimeException;

class Repository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(string $name, ?string $originCountry, int $tmdbId) : Entity
    {
        $this->dbConnection->insert(
            'company',
            [
                'name' => $name,
                'origin_country' => $originCountry,
                'tmdb_id' => $tmdbId,
            ]
        );

        return $this->fetchById((int)$this->dbConnection->lastInsertId());
    }

    public function delete(int $tmdbId) : void
    {
        $this->dbConnection->delete('company', ['tmdb_id' => $tmdbId]);
    }

    public function findByNameAndOriginCountry(string $name, ?string $originCountry) : ?Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `company` WHERE name = ? AND origin_country = ?', [$name, $originCountry]);

        if ($data === false) {
            return null;
        }

        return Entity::createFromArray($data);
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `company` WHERE tmdb_id = ?', [$tmdbId]);

        if ($data === false) {
            return null;
        }

        return Entity::createFromArray($data);
    }

    public function update(int $id, string $name, ?string $originCountry) : Entity
    {
        $this->dbConnection->update(
            'company',
            [
                'name' => $name,
                'origin_country' => $originCountry,
            ],
            [
                'id' => $id,
            ]
        );

        return $this->fetchById($id);
    }

    private function fetchById(int $id) : Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `company` WHERE id = ?', [$id]);

        if ($data === false) {
            throw new RuntimeException('No company found by id: ' . $id);
        }

        return Entity::createFromArray($data);
    }
}
