<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Mapper;

use Movary\Domain\User\UserApi;
use Movary\HttpController\Web\Dto\MoviesRequestDto;
use Movary\Service\ListPagePreference;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;
use RuntimeException;

class WatchlistRequestMapper
{
    private const array FILTER_VALUES = ['ge', 'la', 'ry', 'pc'];

    private const array SORT_BY_VALUES = ['addedAt', 'title', 'releaseDate', 'rating', 'runtime'];

    private const null DEFAULT_PRODUCTION_COUNTRY_CODE = null;

    private const null DEFAULT_GENRE = null;

    private const null DEFAULT_RELEASE_YEAR = null;

    private const int DEFAULT_LIMIT = 24;

    private const int DEFAULT_PAGE = 1;

    private const string DEFAULT_SORT_BY = 'addedAt';

    public function __construct(
        private readonly UserApi $userApi,
        private readonly ListPagePreference $listPagePreference,
    ) {
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function mapRenderPageRequest(Request $request) : MoviesRequestDto
    {
        $userId = $this->userApi->fetchUserByName((string)$request->getRouteParameters()['username'])->getId();

        $getParameters = $request->getGetParameters();
        $savedPreference = $this->listPagePreference->find(
            $request,
            ListPagePreference::PAGE_WATCHLIST,
            self::SORT_BY_VALUES,
            self::FILTER_VALUES,
        );
        $savedFilters = $savedPreference['filters'] ?? [];
        $filterParameters = [...$savedFilters, ...$getParameters];

        $searchTerm = $getParameters['s'] ?? null;
        $page = $getParameters['p'] ?? self::DEFAULT_PAGE;
        $limit = $getParameters['pp'] ?? self::DEFAULT_LIMIT;
        $sortBy = $getParameters['sb'] ?? $savedPreference['sortBy'] ?? self::DEFAULT_SORT_BY;
        $sortOrder = $this->mapSortOrder(
            $getParameters,
            $savedPreference['sortOrder'] ?? SortOrder::createDesc(),
        );
        $releaseYear = $filterParameters['ry'] ?? self::DEFAULT_RELEASE_YEAR;
        $releaseYear = empty($releaseYear) === false ? Year::createFromString($releaseYear) : null;
        $language = $filterParameters['la'] ?? null;
        $language = $language === '' ? null : $language;
        $genre = $filterParameters['ge'] ?? self::DEFAULT_GENRE;
        $genre = $genre === '' ? null : $genre;
        $productionCountryCode = $filterParameters['pc'] ?? self::DEFAULT_PRODUCTION_COUNTRY_CODE;
        $productionCountryCode = $productionCountryCode === '' ? null : $productionCountryCode;

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
            $productionCountryCode,
        );
    }

    private function mapSortOrder(array $getParameters, SortOrder $default) : SortOrder
    {
        if (isset($getParameters['so']) === false) {
            return $default;
        }

        return match ($getParameters['so']) {
            'asc' => SortOrder::createAsc(),
            'desc' => SortOrder::createDesc(),

            default => throw new RuntimeException('Not supported sort order: ' . $getParameters['so'])
        };
    }
}
