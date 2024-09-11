<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Mapper;

use Movary\Domain\User\UserApi;
use Movary\HttpController\Web\Dto\MoviesRequestDto;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;
use RuntimeException;

class MoviesRequestMapper
{
    private const null DEFAULT_HAS_USER_RATING = null;

    private const null DEFAULT_USER_RATING_MIN = null;

    private const null DEFAULT_USER_RATING_MAX = null;

    private const null DEFAULT_GENRE = null;

    private const null DEFAULT_RELEASE_YEAR = null;

    private const int DEFAULT_LIMIT = 24;

    private const string DEFAULT_SORT_BY = 'title';

    public function __construct(
        private readonly UserApi $userApi,
    ) {
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function mapRenderPageRequest(Request $request) : MoviesRequestDto
    {
        $userId = $this->userApi->fetchUserByName((string)$request->getRouteParameters()['username'])->getId();

        $getParameters = $request->getGetParameters();

        $searchTerm = $getParameters['s'] ?? null;
        $page = $getParameters['p'] ?? 1;
        $limit = $getParameters['pp'] ?? self::DEFAULT_LIMIT;
        $sortBy = $getParameters['sb'] ?? self::DEFAULT_SORT_BY;
        $sortOrder = $this->mapSortOrder($getParameters);
        $releaseYear = $getParameters['ry'] ?? self::DEFAULT_RELEASE_YEAR;
        $releaseYear = empty($releaseYear) === false ? Year::createFromString($releaseYear) : null;
        $language = $getParameters['la'] ?? null;
        $genre = $getParameters['ge'] ?? self::DEFAULT_GENRE;
        $userRating = isset($getParameters['ur']) === true ? (bool)$getParameters['ur'] : self::DEFAULT_HAS_USER_RATING;
        $userRatingMin = isset($getParameters['minur']) === true ? (int)$getParameters['minur'] : self::DEFAULT_USER_RATING_MIN;
        $userRatingMax = isset($getParameters['maxur']) === true ? (int)$getParameters['maxur'] : self::DEFAULT_USER_RATING_MAX;
        $locationId = isset($getParameters['loc']) === true ? (int)$getParameters['loc'] : null;

        return MoviesRequestDto::createFromParameters(
            $userId,
            $searchTerm,
            (int)$page,
            (int)$limit,
            $sortBy,
            $sortOrder,
            $releaseYear,
            $language,
            $genre,
            $userRating,
            $userRatingMin,
            $userRatingMax,
            $locationId,
        );
    }

    private function mapSortOrder(array $getParameters) : SortOrder
    {
        if (isset($getParameters['so']) === false) {
            return SortOrder::createAsc();
        }

        return match ($getParameters['so']) {
            'asc' => SortOrder::createAsc(),
            'desc' => SortOrder::createDesc(),

            default => throw new RuntimeException('Not supported sort order: ' . $getParameters['so'])
        };
    }
}
