<?php

namespace Movary\HttpController\Mapper;

use Movary\Application\User\Service\UserPageAuthorizationChecker;
use Movary\HttpController\Dto\MoviesRequestDto;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Year;

class MoviesRequestMapper
{
    private const DEFAULT_RELEASE_YEAR = null;

    private const DEFAULT_LIMIT = 24;

    private const DEFAULT_SORT_BY = 'title';

    private const DEFAULT_SORT_ORDER = 'ASC';

    public function __construct(
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
    ) {
    }

    public function mapRenderPageRequest(Request $request) : MoviesRequestDto
    {
        $userId = $this->userPageAuthorizationChecker->findUserIdIfCurrentVisitorIsAllowedToSeeUser((string)$request->getRouteParameters()['username']);

        $searchTerm = $request->getGetParameters()['s'] ?? null;
        $page = $request->getGetParameters()['p'] ?? 1;
        $limit = $request->getGetParameters()['pp'] ?? self::DEFAULT_LIMIT;
        $sortBy = $request->getGetParameters()['sb'] ?? self::DEFAULT_SORT_BY;
        $sortOrder = $request->getGetParameters()['so'] ?? self::DEFAULT_SORT_ORDER;
        $releaseYear = $request->getGetParameters()['ry'] ?? self::DEFAULT_RELEASE_YEAR;
        $releaseYear = empty($releaseYear) === false ? Year::createFromString($releaseYear) : null;
        $language = $request->getGetParameters()['la'] ?? null;

        return MoviesRequestDto::createFromParameters(
            $userId,
            $searchTerm,
            $page,
            $limit,
            $sortBy,
            $sortOrder,
            $releaseYear,
            $language,
        );
    }
}
