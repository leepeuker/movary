<?php declare(strict_types=1);

namespace Movary\Application\Movie\History;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Date;

class Repository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(int $movieId, Date $watchedAt) : void
    {
        $this->dbConnection->insert(
            'movie_history',
            [
                'movie_id' => $movieId,
                'watched_at' => (string)$watchedAt,
            ]
        );
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->dbConnection->delete('movie_history', ['movie_id' => $movieId]);
    }
}
