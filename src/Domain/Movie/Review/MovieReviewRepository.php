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

    public function fetchVisibleForMovie(int $movieId, ?int $viewerUserId) : array
    {
        $rows = $this->dbConnection->fetchAllAssociative(
            'SELECT review.movie_id, review.user_id, review.review, review.contains_spoilers,
                review.created_at, review.updated_at, author.name AS username,
                rating.rating AS personal_rating
            FROM `movie_user_review` review
            JOIN `user` author ON author.id = review.user_id
            LEFT JOIN `movie_user_rating` rating
                ON rating.movie_id = review.movie_id AND rating.user_id = review.user_id
            WHERE review.movie_id = ?
                AND (author.privacy_level = 2
                    OR (? IS NOT NULL AND author.privacy_level >= 1)
                    OR author.id = ?)
            ORDER BY COALESCE(review.updated_at, review.created_at) DESC, author.name',
            [$movieId, $viewerUserId, $viewerUserId],
        );

        return array_map(
            static fn(array $row) : array => [
                'movieId' => (int)$row['movie_id'],
                'userId' => (int)$row['user_id'],
                'username' => (string)$row['username'],
                'review' => (string)$row['review'],
                'containsSpoilers' => (bool)$row['contains_spoilers'],
                'personalRating' => $row['personal_rating'] === null ? null : (int)$row['personal_rating'],
                'createdAt' => DateTime::createFromString((string)$row['created_at']),
                'updatedAt' => $row['updated_at'] === null
                    ? null
                    : DateTime::createFromString((string)$row['updated_at']),
            ],
            $rows,
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
}
