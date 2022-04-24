<?php declare(strict_types=1);

namespace Movary\Application\Movie\Genre;

use Doctrine\DBAL\Connection;

class Repository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(int $movieId, int $genreId, int $position) : void
    {
        $this->dbConnection->insert(
            'movie_genre',
            [
                'movie_id' => $movieId,
                'genre_id' => $genreId,
                'position' => $position,
            ]
        );
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->dbConnection->delete('movie_genre', ['movie_id' => $movieId]);
    }
}
