<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;
use RuntimeException;

class RequestMapper
{
    private const null DEFAULT_LANGUAGE = null;

    private const null DEFAULT_GENRE = null;

    private const null DEFAULT_LOCATION_ID = null;

    private const int DEFAULT_PAGE = 1;

    private const int DEFAULT_LIMIT = 24;

    private const string DEFAULT_SORT_ORDER = 'desc';

    public function __construct(
        private readonly UserApi $userApi,
    ) {
    }

    public function mapLanguage(Request $request) : ?string
    {
        $getParameters = $request->getGetParameters();

        $language = $getParameters['language'] ?? self::DEFAULT_LANGUAGE;

        return $language !== null ? (string)$language : null;
    }

    public function mapGenre(Request $request) : ?string
    {
        $getParameters = $request->getGetParameters();

        $genre = $getParameters['genre'] ?? self::DEFAULT_GENRE;

        return $genre !== null ? (string)$genre : null;
    }

    public function mapLimit(Request $request) : int
    {
        $getParameters = $request->getGetParameters();

        $limit = $getParameters['limit'] ?? self::DEFAULT_LIMIT;

        return (int)$limit;
    }

    public function mapLocationId(Request $request) : ?int
    {
        $getParameters = $request->getGetParameters();

        $locationId = $getParameters['locationId'] ?? self::DEFAULT_LOCATION_ID;

        return $locationId !== null ? (int)$locationId : null;
    }

    public function mapPage(Request $request) : int
    {
        $getParameters = $request->getGetParameters();

        $page = $getParameters['page'] ?? self::DEFAULT_PAGE;

        return (int)$page;
    }

    public function mapReleaseYear(Request $request) : ?Year
    {
        $getParameters = $request->getGetParameters();

        if (isset($getParameters['releaseYear']) === false) {
            return null;
        }

        return Year::createFromString($getParameters['releaseYear']);
    }

    public function mapSearchTerm(Request $request) : ?string
    {
        $getParameters = $request->getGetParameters();

        return $getParameters['search'] ?? null;
    }

    public function mapSortOrder(Request $request, ?string $defaultSortOrder = null) : SortOrder
    {
        $getParameters = $request->getGetParameters();

        $sortOrder = $getParameters['sortOrder'] ?? $defaultSortOrder ?? self::DEFAULT_SORT_ORDER;

        return match ($sortOrder) {
            'asc' => SortOrder::createAsc(),
            'desc' => SortOrder::createDesc(),

            default => throw new RuntimeException('Not supported sort order: ' . $getParameters['sortOrder'])
        };
    }

    public function mapUsernameFromRoute(Request $request) : UserEntity
    {
        $username = $request->getRouteParameters()['username'] ?? null;

        if ($username === null) {
            throw new RuntimeException('Username parameter missing in route');
        }

        return $this->userApi->fetchUserByName($username);
    }
}
