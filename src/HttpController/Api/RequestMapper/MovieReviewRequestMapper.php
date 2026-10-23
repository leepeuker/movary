<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Movie\Review\MovieReviewApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\Http\Request;

class MovieReviewRequestMapper
{
    public function __construct(private readonly RequestMapper $requestMapper)
    {
    }

    /** @return array{review: string, containsSpoilers: bool} */
    public function mapReview(Request $request) : array
    {
        $data = $this->requestMapper->mapJsonObject($request);

        $review = $data['review'] ?? null;
        if (is_string($review) === false || trim($review) === '') {
            throw InvalidApiRequest::invalidField('review', 'is required and must be a non-empty string');
        }
        if (mb_strlen($review, 'UTF-8') > MovieReviewApi::MAX_REVIEW_LENGTH) {
            throw InvalidApiRequest::invalidField('review', 'must not exceed 10000 characters');
        }

        $containsSpoilers = $data['containsSpoilers'] ?? false;
        if (is_bool($containsSpoilers) === false) {
            throw InvalidApiRequest::invalidField('containsSpoilers', 'must be a boolean');
        }

        return [
            'review' => $review,
            'containsSpoilers' => $containsSpoilers,
        ];
    }
}
