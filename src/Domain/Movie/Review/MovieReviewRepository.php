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

    public function create(int $movieId, int $userId, string $review, bool $containsSpoilers) : void
    {
        $this->dbConnection->insert(
            self::TABLE_NAME,
            [
                'movie_id' => $movieId,
                'user_id' => $userId,
                'review' => $review,
                'contains_spoilers' => (int)$containsSpoilers,
                'created_at' => (string)DateTime::create(),
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

    public function find(int $movieId, int $userId) : ?MovieReviewEntity
    {
        $data = $this->dbConnection->fetchAssociative(
            'SELECT * FROM `movie_user_review` WHERE movie_id = ? AND user_id = ?',
            [$movieId, $userId],
        );

        return $data === false ? null : MovieReviewEntity::createFromArray($data);
    }

    public function update(int $movieId, int $userId, string $review, bool $containsSpoilers) : void
    {
        $this->dbConnection->update(
            self::TABLE_NAME,
            [
                'review' => $review,
                'contains_spoilers' => (int)$containsSpoilers,
                'updated_at' => (string)DateTime::create(),
            ],
            [
                'movie_id' => $movieId,
                'user_id' => $userId,
            ],
        );
    }
}
