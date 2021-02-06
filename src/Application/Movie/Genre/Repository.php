<?php declare(strict_types=1);

namespace Movary\Application\Movie\Genre;

use Doctrine\DBAL\Connection;

class Repository
{
    private Connection $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
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
