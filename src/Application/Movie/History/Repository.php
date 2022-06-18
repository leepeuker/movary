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

    public function deleteByTraktId(TraktId $traktId) : void
    {
        $this->dbConnection->executeStatement(
            'DELETE movie_history
            FROM movie_history
            INNER JOIN movie m ON movie_history.movie_id = m.id
            WHERE m.trakt_id = ?',
            [$traktId->asInt()]
        );
    }

    public function deleteHistoryByIdAndDate(int $movieId, Date $watchedAt) : void
    {
        $this->dbConnection->executeStatement(
            'DELETE movie_history
            FROM movie_history
            WHERE movie_id = ? AND watched_at = ?',
            [$movieId, (string)$watchedAt]
        );
    }
}
