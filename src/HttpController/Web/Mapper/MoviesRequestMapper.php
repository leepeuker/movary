<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Mapper;

use Movary\Domain\User\UserApi;
use Movary\HttpController\Web\Dto\MoviesRequestDto;
use Movary\Service\ListPagePreference;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;
use RuntimeException;

class MoviesRequestMapper
{
    private const array FILTER_VALUES = ['ge', 'la', 'ry', 'pc', 'ur', 'minur', 'maxur', 'loc'];

    private const array SORT_BY_VALUES = [
        'title',
        'releaseDate',
        'watchDate',
        'rating',
        'imdbRating',
        'tmdbRating',
        'runtime',
    ];

    private const int DEFAULT_LIMIT = 24;

    private const string DEFAULT_SORT_BY = 'title';

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
            ListPagePreference::PAGE_MOVIES,
            self::SORT_BY_VALUES,
            self::FILTER_VALUES,
        );
        $savedFilters = $savedPreference['filters'] ?? [];
        $filterParameters = [...$savedFilters, ...$getParameters];

        $searchTerm = $getParameters['s'] ?? null;
        $page = $getParameters['p'] ?? 1;
        $limit = $getParameters['pp'] ?? self::DEFAULT_LIMIT;
        $sortBy = $getParameters['sb'] ?? $savedPreference['sortBy'] ?? self::DEFAULT_SORT_BY;
        $sortOrder = $this->mapSortOrder(
            $getParameters,
            $savedPreference['sortOrder'] ?? SortOrder::createAsc(),
        );
        $releaseYear = $this->mapReleaseYear($filterParameters);
        $language = $this->mapOptionalString($filterParameters, 'la');
        $genre = $this->mapOptionalString($filterParameters, 'ge');
        $productionCountryCode = $this->mapOptionalString($filterParameters, 'pc');
        $userRating = $this->mapOptionalBool($filterParameters, 'ur');
        $userRatingMin = $this->mapOptionalInt($filterParameters, 'minur');
        $userRatingMax = $this->mapOptionalInt($filterParameters, 'maxur');
        $locationId = $this->mapOptionalInt($filterParameters, 'loc');

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
            $userRating,
            $userRatingMin,
            $userRatingMax,
            $locationId,
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

    private function mapOptionalBool(array $parameters, string $name) : ?bool
    {
        $value = $parameters[$name] ?? null;

        return $value === null || $value === '' ? null : (bool)$value;
    }

    private function mapOptionalInt(array $parameters, string $name) : ?int
    {
        $value = $parameters[$name] ?? null;

        return $value === null || $value === '' ? null : (int)$value;
    }

    private function mapOptionalString(array $parameters, string $name) : ?string
    {
        $value = $parameters[$name] ?? null;

        return is_string($value) === true && $value !== '' ? $value : null;
    }

    private function mapReleaseYear(array $parameters) : ?Year
    {
        $releaseYear = $this->mapOptionalString($parameters, 'ry');

        return $releaseYear === null ? null : Year::createFromString($releaseYear);
    }
}
