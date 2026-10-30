<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\PersonalRating;
use RuntimeException;

class MovieRatingRequestMapper
{
    public function __construct(private readonly RequestMapper $requestMapper)
    {
    }

    public function mapRating(Request $request) : PersonalRating
    {
        $data = $this->requestMapper->mapJsonObject($request);
        $rating = $data['rating'] ?? null;
        if (is_int($rating) === false) {
            throw InvalidApiRequest::invalidField('rating', 'must be an integer between 1 and 10');
        }

        try {
            return PersonalRating::create($rating);
        } catch (RuntimeException) {
            throw InvalidApiRequest::invalidField('rating', 'must be an integer between 1 and 10');
        }
    }
}
