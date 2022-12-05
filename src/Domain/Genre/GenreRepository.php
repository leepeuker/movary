<?php declare(strict_types=1);

namespace Movary\Domain\Genre;

use Doctrine\DBAL\Connection;
use RuntimeException;

class GenreRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(string $name, int $tmdbId) : GenreEntity
    {
        $this->dbConnection->insert(
            'genre',
            [
                'name' => $name,
                'tmdb_id' => $tmdbId,
            ],
        );

        return $this->fetchById((int)$this->dbConnection->lastInsertId());
    }

    public function findById(int $id) : ?GenreEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `genre` WHERE id = ?', [$id]);

        if ($data === false) {
            return null;
        }

        return GenreEntity::createFromArray($data);
    }

    public function findByTmdbId(int $tmdbId) : ?GenreEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `genre` WHERE tmdb_id = ?', [$tmdbId]);

        if ($data === false) {
            return null;
        }

        return GenreEntity::createFromArray($data);
    }

    private function fetchById(int $id) : GenreEntity
    {
        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `genre` WHERE id = ?', [$id]);

        if ($data === false) {
            throw new RuntimeException('No genre found by id: ' . $id);
        }

        return GenreEntity::createFromArray($data);
    }
}
