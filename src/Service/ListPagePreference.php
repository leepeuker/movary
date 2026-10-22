<?php declare(strict_types=1);

namespace Movary\Service;

use JsonException;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;

class ListPagePreference
{
    public const string COOKIE_NAME = 'movary_list_preferences';

    public const string PAGE_ACTORS = 'actors';

    public const string PAGE_DIRECTORS = 'directors';

    public const string PAGE_MOVIES = 'movies';

    public const string PAGE_WATCHLIST = 'watchlist';

    private const int COOKIE_VERSION = 1;

    private const array PER_PAGE_VALUES = ['24', '48', '72', '96'];

    /**
     * @param list<string> $allowedSortBy
     * @param list<string> $allowedFilters
     * @return array{sortBy: string, sortOrder: SortOrder, filters: array<string, string>, perPage: ?int}|null
     */
    public function find(
        Request $request,
        string $page,
        array $allowedSortBy,
        array $allowedFilters,
    ) : ?array {
        $preferences = $this->decode($request);

        if ($preferences === null
            || ($preferences['version'] ?? null) !== self::COOKIE_VERSION
            || is_array($preferences['pages'] ?? null) === false
            || is_array($preferences['pages'][$page] ?? null) === false
        ) {
            return null;
        }

        $preference = $preferences['pages'][$page];
        $sortBy = $preference['sortBy'] ?? null;
        $sortOrder = $preference['sortOrder'] ?? null;

        if (is_string($sortBy) === false
            || in_array($sortBy, $allowedSortBy, true) === false
            || in_array($sortOrder, ['asc', 'desc'], true) === false
        ) {
            return null;
        }

        return [
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder === 'asc' ? SortOrder::createAsc() : SortOrder::createDesc(),
            'filters' => $this->mapFilters($preference['filters'] ?? null, $allowedFilters),
            'perPage' => $this->mapPerPage($preference['perPage'] ?? null),
        ];
    }

    private function decode(Request $request) : ?array
    {
        $cookie = $request->getCookie(self::COOKIE_NAME);
        if ($cookie === null) {
            return null;
        }

        try {
            $preferences = json_decode(rawurldecode($cookie), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($preferences) === true ? $preferences : null;
    }

    /**
     * @param list<string> $allowedFilters
     * @return array<string, string>
     */
    private function mapFilters(mixed $filterData, array $allowedFilters) : array
    {
        if (is_array($filterData) === false) {
            return [];
        }

        $filters = [];
        foreach ($filterData as $name => $value) {
            if (is_string($name) === true
                && in_array($name, $allowedFilters, true) === true
                && is_string($value) === true
                && $value !== ''
            ) {
                $filters[$name] = $value;
            }
        }

        return $filters;
    }

    private function mapPerPage(mixed $perPage) : ?int
    {
        if (is_string($perPage) === false || in_array($perPage, self::PER_PAGE_VALUES, true) === false) {
            return null;
        }

        return (int)$perPage;
    }
}
