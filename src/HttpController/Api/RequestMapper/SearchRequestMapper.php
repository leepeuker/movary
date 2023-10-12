<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Dto\SearchRequestDto;
use Movary\ValueObject\Http\Request;

class SearchRequestMapper
{
    public function __construct(private readonly RequestMapper $requestMapper)
    {
    }

    public function mapRequest(Request $request) : SearchRequestDto
    {
        $getParameters = $request->getGetParameters();

        return SearchRequestDto::create(
            $getParameters['search'],
            $this->requestMapper->mapPage($request),
            $this->requestMapper->mapReleaseYear($request),
        );
    }
}
