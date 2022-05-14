<?php declare(strict_types=1);

namespace Movary\Application\Movie\History;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Date;

class Repository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function createOrUpdatePlaysForDate(int $movieId, Date $watchedAt, int $plays) : void
    {
        $this->dbConnection->executeStatement(
            'REPLACE INTO movie_history (movie_id, watched_at, plays) VALUES (?, ?, ?)',
            [
                $movieId,
                (string)$watchedAt,
                (string)$plays,
            ]
        );
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->dbConnection->delete('movie_history', ['movie_id' => $movieId]);
    }
}
