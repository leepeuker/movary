<?php declare(strict_types=1);

namespace Movary\Application\Movie\History;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\DateTime;

class Repository
{
    private Connection $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    public function create(int $movieId, DateTime $watchedAt) : void
    {
        $this->dbConnection->insert(
            'movie_history',
            [
                'movie_id' => $movieId,
                'watched_at' => (string)$watchedAt
            ]
        );
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->dbConnection->delete('movie_history', ['movie_id' => $movieId]);
    }
}
