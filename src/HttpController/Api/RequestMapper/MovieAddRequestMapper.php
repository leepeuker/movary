<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Dto\MovieAddRequestDto;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\Http\Request;

class MovieAddRequestMapper
{
    public function __construct(private readonly RequestMapper $requestMapper)
    {
    }

    public function mapRequest(Request $request) : MovieAddRequestDto
    {
        $requestBody = $this->requestMapper->mapJsonObject($request);
        if (array_key_exists('tmdbId', $requestBody) === false) {
            throw InvalidApiRequest::missingProviderId();
        }

        return MovieAddRequestDto::create(
            $this->requestMapper->mapRequiredPositiveInteger($requestBody, 'tmdbId', 'tmdbId'),
        );
    }
}
