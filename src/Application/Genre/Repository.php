<?php declare(strict_types=1);

namespace Movary\Application\Genre;

use Doctrine\DBAL\Connection;
use RuntimeException;

class Repository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(string $name, int $tmdbId) : Entity
    {
        $this->dbConnection->insert(
            'genre',
            [
                'name' => $name,
                'tmdb_id' => $tmdbId,
            ]
        );

        return $this->fetchById((int)$this->dbConnection->lastInsertId());
    }

    public function findById(int $id) : ?Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `genre` WHERE id = ?', [$id]);

        if ($data === false) {
            return null;
        }

        return Entity::createFromArray($data);
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `genre` WHERE tmdb_id = ?', [$tmdbId]);

        if ($data === false) {
            return null;
        }

        return Entity::createFromArray($data);
    }

    private function fetchById(int $id) : Entity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `genre` WHERE id = ?', [$id]);

        if ($data === false) {
            throw new RuntimeException('No genre found by id: ' . $id);
        }

        return Entity::createFromArray($data);
    }
}
