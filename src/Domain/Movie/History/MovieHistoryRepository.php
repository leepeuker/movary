<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History;

use Doctrine\DBAL\Connection;
use Movary\Util\Json;
use Movary\ValueObject\Date;

class MovieHistoryRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function createPlay(int $movieId, int $userId, ?Date $watchedAt, ?string $comment, int $position, ?int $locationId) : int
    {
        $this->dbConnection->executeStatement(
            'INSERT INTO movie_user_watch_dates (movie_id, user_id, watched_at, plays, `comment`, `position`, `location_id`) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $movieId,
                $userId,
                $watchedAt !== null ? (string)$watchedAt : null,
                1,
                $comment,
                $position,
                $locationId,
            ],
        );

        return (int)$this->dbConnection->lastInsertId();
    }

    public function findPlayById(int $playId, int $userId) : ?MovieHistoryEntity
    {
        $data = $this->dbConnection->fetchAssociative(
            'SELECT * FROM movie_user_watch_dates WHERE id = ? AND user_id = ?',
            [$playId, $userId],
        );

        return $data === false ? null : MovieHistoryEntity::createFromArray($data);
    }

    public function fetchPlaysByMovieAndDate(int $movieId, int $userId, ?Date $watchedAt) : array
    {
        if ($watchedAt === null) {
            return $this->dbConnection->fetchAllAssociative(
                'SELECT * FROM movie_user_watch_dates WHERE movie_id = ? AND user_id = ? AND watched_at IS NULL ORDER BY id',
                [$movieId, $userId],
            );
        }

        return $this->dbConnection->fetchAllAssociative(
            'SELECT * FROM movie_user_watch_dates WHERE movie_id = ? AND user_id = ? AND watched_at = ? ORDER BY id',
            [$movieId, $userId, (string)$watchedAt],
        );
    }

    public function deletePlayById(int $playId, int $userId) : void
    {
        $this->dbConnection->delete('movie_user_watch_dates', ['id' => $playId, 'user_id' => $userId]);
    }

    public function hasMastodonJobReference(int $playId, int $userId) : bool
    {
        $parameters = $this->dbConnection->fetchFirstColumn(
            "SELECT parameters FROM job_queue WHERE user_id = ? AND job_type = 'mastodon_post_play' AND job_status IN ('waiting', 'in progress', 'done')",
            [$userId],
        );

        foreach ($parameters as $encoded) {
            if ($encoded !== null && (int)(Json::decode((string)$encoded)['playId'] ?? 0) === $playId) {
                return true;
            }
        }

        return false;
    }

    public function updatePlayById(
        int $playId,
        int $userId,
        ?Date $watchedAt,
        int $position,
        ?string $comment,
        ?int $locationId,
    ) : void {
        $this->dbConnection->update(
            'movie_user_watch_dates',
            [
                'watched_at' => $watchedAt === null ? null : (string)$watchedAt,
                'position' => $position,
                'comment' => $comment,
                'location_id' => $locationId,
            ],
            ['id' => $playId, 'user_id' => $userId],
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
        if ($watchedAt === null) {
            return $this->dbConnection->fetchFirstColumn(
                'SELECT MAX(position)
                FROM movie_user_watch_dates
                WHERE movie_id != ? AND watched_at IS NULL AND user_id = ?',
                [$movieIdToIgnore, $userId],
            )[0];
        }

        return $this->dbConnection->fetchFirstColumn(
            'SELECT MAX(position)
            FROM movie_user_watch_dates
            WHERE movie_id != ? AND watched_at = ? AND user_id = ?',
            [
                $movieIdToIgnore,
                (string)$watchedAt,
                $userId
            ],
        )[0];
    }

    public function updateHistoryComment(int $movieId, int $userId, ?Date $watchedAt, ?string $comment) : void
    {
        $rows = $this->fetchPlaysByMovieAndDate($movieId, $userId, $watchedAt);
        if ($rows === []) {
            return;
        }

        $this->dbConnection->update('movie_user_watch_dates', ['comment' => $comment], ['id' => $rows[0]['id'], 'user_id' => $userId]);
    }

    public function updateHistoryLocation(int $movieId, int $userId, ?Date $watchedAt, ?int $locationId) : void
    {
        $rows = $this->fetchPlaysByMovieAndDate($movieId, $userId, $watchedAt);
        if ($rows === []) {
            return;
        }

        $this->dbConnection->update('movie_user_watch_dates', ['location_id' => $locationId], ['id' => $rows[0]['id'], 'user_id' => $userId]);
    }
}
