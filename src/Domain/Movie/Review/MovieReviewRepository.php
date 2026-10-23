<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Review;

use Doctrine\DBAL\Connection;
use Movary\ValueObject\DateTime;

class MovieReviewRepository
{
    private const string TABLE_NAME = 'movie_user_review';

    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(
        int $movieId,
        int $userId,
        string $review,
        bool $containsSpoilers,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null,
    ) : void {
        $this->dbConnection->insert(
            self::TABLE_NAME,
            [
                'movie_id' => $movieId,
                'user_id' => $userId,
                'review' => $review,
                'contains_spoilers' => (int)$containsSpoilers,
                'created_at' => (string)($createdAt ?? DateTime::create()),
                'updated_at' => $updatedAt === null ? null : (string)$updatedAt,
            ],
        );
    }

    public function delete(int $movieId, int $userId) : void
    {
        $this->dbConnection->delete(
            self::TABLE_NAME,
            [
                'movie_id' => $movieId,
                'user_id' => $userId,
            ],
        );
    }

    public function deleteAllForUser(int $userId) : void
    {
        $this->dbConnection->delete(self::TABLE_NAME, ['user_id' => $userId]);
    }

    public function fetchAllForUser(int $userId) : array
    {
        return $this->dbConnection->fetchAllAssociative(
            'SELECT movie.title, movie.release_date, movie.tmdb_id, movie.imdb_id,
                review.review, review.contains_spoilers, review.created_at, review.updated_at
            FROM `movie_user_review` review
            JOIN `movie` movie ON movie.id = review.movie_id
            WHERE review.user_id = ?
            ORDER BY movie.title, movie.id',
            [$userId],
        );
    }

    public function find(int $movieId, int $userId) : ?MovieReviewEntity
    {
        $data = $this->dbConnection->fetchAssociative(
            'SELECT * FROM `movie_user_review` WHERE movie_id = ? AND user_id = ?',
            [$movieId, $userId],
        );

        return $data === false ? null : MovieReviewEntity::createFromArray($data);
    }

    public function update(
        int $movieId,
        int $userId,
        string $review,
        bool $containsSpoilers,
        ?DateTime $updatedAt = null,
    ) : void {
        $this->dbConnection->update(
            self::TABLE_NAME,
            [
                'review' => $review,
                'contains_spoilers' => (int)$containsSpoilers,
                'updated_at' => (string)($updatedAt ?? DateTime::create()),
            ],
            [
                'movie_id' => $movieId,
                'user_id' => $userId,
            ],
        );
    }

    public function updateFromImport(
        int $movieId,
        int $userId,
        string $review,
        bool $containsSpoilers,
        DateTime $createdAt,
        ?DateTime $updatedAt,
    ) : void {
        $this->dbConnection->update(
            self::TABLE_NAME,
            [
                'review' => $review,
                'contains_spoilers' => (int)$containsSpoilers,
                'created_at' => (string)$createdAt,
                'updated_at' => $updatedAt === null ? null : (string)$updatedAt,
            ],
            [
                'movie_id' => $movieId,
                'user_id' => $userId,
            ],
        );
    }
}
