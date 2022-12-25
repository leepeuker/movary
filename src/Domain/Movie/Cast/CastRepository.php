<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Cast;

use Doctrine\DBAL\Connection;

class CastRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(int $movieId, int $personId, ?string $character, int $position) : void
    {
        $this->dbConnection->insert(
            'movie_cast',
            [
                'movie_id' => $movieId,
                'person_id' => $personId,
                'character_name' => $character,
                'position' => $position,
            ],
        );
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->dbConnection->delete('movie_cast', ['movie_id' => $movieId]);
    }

    public function findByMovieId(int $movieId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT p.id, p.name, p.poster_path, p.tmdb_poster_path
            FROM movie_cast c
            JOIN person p on c.person_id = p.id
            WHERE c.movie_id = ?
            ORDER BY c.position',
            [$movieId],
        );
    }
}
