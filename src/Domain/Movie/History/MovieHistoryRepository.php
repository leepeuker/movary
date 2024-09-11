<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\Date;

class MovieHistoryRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(int $movieId, int $userId, ?Date $watchedAt, int $plays, ?string $comment, int $position, ?int $locationId) : void
    {
        $this->dbConnection->executeStatement(
            'INSERT INTO movie_user_watch_dates (movie_id, user_id, watched_at, plays, `comment`, `position`, `location_id`) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $movieId,
                $userId,
                $watchedAt !== null ? (string)$watchedAt : null,
                (string)$plays,
                $comment,
                $position,
                $locationId,
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

    public function deleteHistoryById(int $movieId, int $userId) : void
    {
        $this->dbConnection->delete('movie_user_watch_dates', ['user_id' => $userId, 'movie_id' => $movieId]);
    }

    public function deleteHistoryByIdAndDate(int $movieId, int $userId, ?Date $watchedAt) : void
    {
        if ($watchedAt === null) {
            $this->dbConnection->executeStatement(
                'DELETE
            FROM movie_user_watch_dates
            WHERE movie_id = ? AND watched_at IS NULL AND user_id = ?',
                [$movieId, $userId],
            );

            return;
        }

        $this->dbConnection->executeStatement(
            'DELETE
            FROM movie_user_watch_dates
            WHERE movie_id = ? AND watched_at = ? AND user_id = ?',
            [$movieId, (string)$watchedAt, $userId],
        );
    }

    public function fetchHighestPositionForWatchDate(int $movieIdToIgnore, int $userId, ?Date $watchedAt) : ?int
    {
        return $this->dbConnection->fetchFirstColumn(
            'SELECT MAX(position)
            FROM movie_user_watch_dates
            WHERE movie_id != ? AND watched_at = ? AND user_id = ?',
            [
                $movieIdToIgnore,
                $watchedAt === null ? null : (string)$watchedAt,
                $userId
            ],
        )[0];
    }

    public function update(
        int $movieId,
        int $userId,
        ?Date $watchedAt,
        int $plays,
        int $position,
        ?string $comment,
        ?int $locationId,
    ) : void {
        if ($watchedAt === null) {
            $this->dbConnection->executeStatement(
                'UPDATE movie_user_watch_dates SET `comment` = ?, `plays` = ?, `position` = ?, `location_id` = ? WHERE movie_id = ? AND user_id = ? AND watched_at IS NULL',
                [
                    $comment,
                    $plays,
                    $position,
                    $locationId,
                    $movieId,
                    $userId,
                ],
            );

            return;
        }

        $this->dbConnection->executeStatement(
            'UPDATE movie_user_watch_dates SET `comment` = ?, `plays` = ? , `position` = ?, `location_id` = ? WHERE movie_id = ? AND user_id = ? AND watched_at = ?',
            [
                $comment,
                $plays,
                $position,
                $locationId,
                $movieId,
                $userId,
                (string)$watchedAt,
            ],
        );
    }

    public function updateHistoryComment(int $movieId, int $userId, ?Date $watchedAt, ?string $comment) : void
    {
        if ($watchedAt === null) {
            $this->dbConnection->executeStatement(
                'UPDATE movie_user_watch_dates SET `comment` = ? WHERE movie_id = ? AND user_id = ? AND watched_at IS NULL ',
                [
                    $comment,
                    $movieId,
                    $userId,
                ],
            );

            return;
        }

        $this->dbConnection->executeStatement(
            'UPDATE movie_user_watch_dates SET `comment` = ? WHERE movie_id = ? AND user_id = ? AND watched_at = ?',
            [
                $comment,
                $movieId,
                $userId,
                (string)$watchedAt,
            ],
        );
    }

    public function updateHistoryLocation(int $movieId, int $userId, ?Date $watchedAt, ?int $locationId) : void
    {
        if ($watchedAt === null) {
            $this->dbConnection->executeStatement(
                'UPDATE movie_user_watch_dates SET `location_id` = ? WHERE movie_id = ? AND user_id = ? AND watched_at IS NULL ',
                [
                    $locationId,
                    $movieId,
                    $userId,
                ],
            );

            return;
        }

        $this->dbConnection->executeStatement(
            'UPDATE movie_user_watch_dates SET `location_id` = ? WHERE movie_id = ? AND user_id = ? AND watched_at = ?',
            [
                $locationId,
                $movieId,
                $userId,
                (string)$watchedAt,
            ],
        );
    }
}
