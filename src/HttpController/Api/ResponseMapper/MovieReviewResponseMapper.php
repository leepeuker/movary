<?php declare(strict_types=1);

namespace Movary\HttpController\Api\ResponseMapper;

use Movary\Domain\Movie\Review\MovieReviewEntity;
use Movary\ValueObject\DateTime;

class MovieReviewResponseMapper
{
    /**
     * @return array{
     *     review: string,
     *     containsSpoilers: bool,
     *     createdAt: DateTime,
     *     updatedAt: null|DateTime
     * }
     */
    public function mapReview(MovieReviewEntity $review) : array
    {
        return [
            'review' => $review->getReview(),
            'containsSpoilers' => $review->containsSpoilers(),
            'createdAt' => $review->getCreatedAt(),
            'updatedAt' => $review->getUpdatedAt(),
        ];
    }
}
