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

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `company` WHERE tmdb_id = ?', [$tmdbId]);

        if ($data === false) {
            return null;
        }

        return Entity::createFromArray($data);
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
