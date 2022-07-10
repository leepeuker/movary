<?php declare(strict_types=1);

namespace Movary\Application\Movie\History;

use Doctrine\DBAL\Connection;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\ValueObject\Date;

class Repository
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
            ]
        );
    }

    public function deleteByTraktId(TraktId $traktId) : void
    {
        $this->dbConnection->executeStatement(
            'DELETE movie_user_watch_dates
            FROM movie_user_watch_dates
            INNER JOIN movie m ON movie_user_watch_dates.movie_id = m.id
            WHERE m.trakt_id = ?',
            [$traktId->asInt()]
        );
    }

    public function deleteHistoryByIdAndDate(int $movieId, int $userId, Date $watchedAt) : void
    {
        $this->dbConnection->executeStatement(
            'DELETE movie_user_watch_dates
            FROM movie_user_watch_dates
            WHERE movie_id = ? AND watched_at = ? and user_id = ?',
            [$movieId, (string)$watchedAt, $userId]
        );
    }
}
