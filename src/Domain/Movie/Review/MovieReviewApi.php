<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Review;

use InvalidArgumentException;
use Movary\ValueObject\DateTime;

class MovieReviewApi
{
    public const int MAX_REVIEW_LENGTH = 10_000;

    public function __construct(private readonly MovieReviewRepository $repository)
    {
    }

    public function create(int $movieId, int $userId, string $review, bool $containsSpoilers) : void
    {
        $this->validateReview($review);
        $this->repository->create($movieId, $userId, $review, $containsSpoilers);
    }

    public function delete(int $movieId, int $userId) : void
    {
        $this->repository->delete($movieId, $userId);
    }

    public function deleteAllForUser(int $userId) : void
    {
        $this->repository->deleteAllForUser($userId);
    }

    public function fetchAllForUser(int $userId) : array
    {
        return $this->repository->fetchAllForUser($userId);
    }

    public function find(int $movieId, int $userId) : ?MovieReviewEntity
    {
        return $this->repository->find($movieId, $userId);
    }

    public function import(
        int $movieId,
        int $userId,
        string $review,
        bool $containsSpoilers,
        DateTime $createdAt,
        ?DateTime $updatedAt,
    ) : void {
        $this->validateReview($review);

        if ($this->repository->find($movieId, $userId) === null) {
            $this->repository->create(
                $movieId,
                $userId,
                $review,
                $containsSpoilers,
                $createdAt,
                $updatedAt,
            );

            return;
        }

        $this->repository->updateFromImport(
            $movieId,
            $userId,
            $review,
            $containsSpoilers,
            $createdAt,
            $updatedAt,
        );
    }

    public function save(int $movieId, int $userId, string $review, bool $containsSpoilers) : void
    {
        if ($this->repository->find($movieId, $userId) === null) {
            $this->create($movieId, $userId, $review, $containsSpoilers);

            return;
        }

        $this->update($movieId, $userId, $review, $containsSpoilers);
    }

    public function update(int $movieId, int $userId, string $review, bool $containsSpoilers) : void
    {
        $this->validateReview($review);
        $this->repository->update($movieId, $userId, $review, $containsSpoilers);
    }

    private function validateReview(string $review) : void
    {
        if (trim($review) === '') {
            throw new InvalidArgumentException('A movie review must not be empty.');
        }

        if (mb_strlen($review, 'UTF-8') > self::MAX_REVIEW_LENGTH) {
            throw new InvalidArgumentException('A movie review must not exceed 10000 characters.');
        }
    }
}
