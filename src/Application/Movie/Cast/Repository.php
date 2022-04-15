<?php declare(strict_types=1);

namespace Movary\Application\Movie\Cast;

use Doctrine\DBAL\Connection;

class Repository
{
    private Connection $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    public function create(int $movieId, int $personId, string $character, int $position) : void
    {
        $this->dbConnection->insert(
            'movie_cast',
            [
                'movie_id' => $movieId,
                'person_id' => $personId,
                'character_name' => $character,
                'position' => $position,
            ]
        );
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->dbConnection->delete('movie_cast', ['movie_id' => $movieId]);
    }
}
