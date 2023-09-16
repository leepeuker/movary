<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Dto\WatchlistRequestDto;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;

class PlayedRequestMapper
{
    private const DEFAULT_RELEASE_YEAR = null;

    private const DEFAULT_LIMIT = 24;

    private const DEFAULT_PAGE = 1;

    private const DEFAULT_SORT_BY = 'title';

    public function __construct(private readonly RequestMapper $requestMapper)
    {
    }

    public function mapRequest(Request $request) : WatchlistRequestDto
    {
        $getParameters = $request->getGetParameters();

        $searchTerm = $getParameters['search'] ?? null;
        $page = $getParameters['page'] ?? self::DEFAULT_PAGE;
        $limit = $getParameters['limit'] ?? self::DEFAULT_LIMIT;
        $sortBy = $getParameters['sortBy'] ?? self::DEFAULT_SORT_BY;
        $sortOrder = $this->mapSortOrder($getParameters);
        $releaseYear = $this->mapReleaseYear($getParameters);

        return WatchlistRequestDto::create(
            $this->requestMapper->mapUsernameFromRoute($request)->getId(),
            $searchTerm,
            (int)$page,
            (int)$limit,
            $sortBy,
            $sortOrder,
            $releaseYear,
        );
    }

    private function mapReleaseYear(array $getParameters) : ?Year
    {
        $releaseYear = $getParameters['releaseYear'] ?? self::DEFAULT_RELEASE_YEAR;

        if (empty($releaseYear) === true) {
            return null;
        }

        return Year::createFromString($releaseYear);
    }

    private function mapSortOrder(array $getParameters) : SortOrder
    {
        if (isset($getParameters['sortOrder']) === false) {
            return SortOrder::createAsc();
        }

        return match ($getParameters['sortOrder']) {
            'asc' => SortOrder::createAsc(),
            'desc' => SortOrder::createDesc(),

            default => throw new \RuntimeException('Not supported sort order: ' . $getParameters['sortOrder'])
        };
    }
}
