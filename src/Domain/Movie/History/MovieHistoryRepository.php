<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Date;

class MovieHistoryRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function createOrUpdatePlaysForDate(int $movieId, int $userId, Date $watchedAt, int $plays) : void
    {
        $this->dbConnection->executeStatement(
            'REPLACE INTO movie_user_watch_dates (movie_id, user_id, watched_at, plays) VALUES (?, ?, ?, ?)',
            [
                $movieId,
                $userId,
                (string)$watchedAt,
                (string)$plays,
            ],
        );
    }

    public function deleteByUserAndMovieId(int $userId, int $movieId) : void
    {
        $this->dbConnection->executeStatement(
            'DELETE FROM movie_user_watch_dates WHERE movie_id = ? AND user_id = ?',
            [$movieId, $userId],
        );
    }

    public function deleteByUserId(int $userId) : void
    {
        $this->dbConnection->delete('movie_user_watch_dates', ['user_id' => $userId]);
    }

    public function deleteHistoryByIdAndDate(int $movieId, int $userId, Date $watchedAt) : void
    {
        $this->dbConnection->executeStatement(
            'DELETE
            FROM movie_user_watch_dates
            WHERE movie_id = ? AND watched_at = ? AND user_id = ?',
            [$movieId, (string)$watchedAt, $userId],
        );
    }
}
