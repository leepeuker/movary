<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Review;

use Movary\ValueObject\DateTime;

class MovieReviewEntity
{
    private function __construct(
        private readonly int $movieId,
        private readonly int $userId,
        private readonly string $review,
        private readonly bool $containsSpoilers,
        private readonly DateTime $createdAt,
        private readonly ?DateTime $updatedAt,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['movie_id'],
            (int)$data['user_id'],
            (string)$data['review'],
            (bool)$data['contains_spoilers'],
            DateTime::createFromString((string)$data['created_at']),
            $data['updated_at'] === null ? null : DateTime::createFromString((string)$data['updated_at']),
        );
    }

    public function containsSpoilers() : bool
    {
        return $this->containsSpoilers;
    }

    public function getCreatedAt() : DateTime
    {
        return $this->createdAt;
    }

    public function getMovieId() : int
    {
        return $this->movieId;
    }

    public function getReview() : string
    {
        return $this->review;
    }

    public function getUpdatedAt() : ?DateTime
    {
        return $this->updatedAt;
    }

    public function getUserId() : int
    {
        return $this->userId;
    }
}
