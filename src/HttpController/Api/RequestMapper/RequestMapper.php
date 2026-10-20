<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use JsonException;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\Date;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;
use RuntimeException;

class RequestMapper
{
    private const int DEFAULT_PAGE = 1;

    private const int DEFAULT_LIMIT = 24;

    private const string DEFAULT_SORT_ORDER = 'desc';

    public function __construct(
        private readonly UserApi $userApi,
        private readonly MovieApi $movieApi,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function mapJsonObjectList(Request $request) : array
    {
        try {
            $decoded = json_decode($request->getBody(), false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw InvalidApiRequest::invalidJson();
        }

        if (is_array($decoded) === false) {
            throw InvalidApiRequest::invalidJson();
        }

        $result = [];
        foreach ($decoded as $index => $item) {
            if ($item instanceof \stdClass === false) {
                throw InvalidApiRequest::invalidField('items[' . $index . ']', 'must be an object');
            }

            /** @var array<string, mixed> $mappedItem */
            $mappedItem = $this->convertJsonObjectsToArrays($item);
            $result[] = $mappedItem;
        }

        return $result;
    }

    private function convertJsonObjectsToArrays(mixed $value) : mixed
    {
        if ($value instanceof \stdClass) {
            $value = get_object_vars($value);
        }

        if (is_array($value) === false) {
            return $value;
        }

        foreach ($value as $key => $nestedValue) {
            $value[$key] = $this->convertJsonObjectsToArrays($nestedValue);
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    public function mapExistingMovieId(array $data, string $path) : int
    {
        $movieId = $this->mapRequiredPositiveInteger($data, 'movaryId', $path . '.movaryId');
        if ($this->movieApi->findById($movieId) === null) {
            throw InvalidApiRequest::movieNotFound($movieId);
        }

        return $movieId;
    }

    /** @param array<string, mixed> $data */
    public function mapRequiredDate(array $data, string $field, string $path) : Date
    {
        $value = $data[$field] ?? null;
        if (is_string($value) === false) {
            throw InvalidApiRequest::invalidField($path, 'must be a date in YYYY-MM-DD format');
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw InvalidApiRequest::invalidField($path, 'must be a date in YYYY-MM-DD format');
        }

        return Date::createFromString($value);
    }

    /** @param array<string, mixed> $data */
    public function mapRequiredPositiveInteger(array $data, string $field, string $path) : int
    {
        $value = $data[$field] ?? null;
        if (is_int($value) === false || $value < 1) {
            throw InvalidApiRequest::invalidField($path, 'must be a positive integer');
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    public function mapOptionalPositiveInteger(array $data, string $field, string $path, int $default) : int
    {
        if (array_key_exists($field, $data) === false) {
            return $default;
        }

        return $this->mapRequiredPositiveInteger($data, $field, $path);
    }

    /** @param array<string, mixed> $data */
    public function mapRequiredNullableString(array $data, string $field, string $path) : ?string
    {
        if (array_key_exists($field, $data) === false) {
            throw InvalidApiRequest::invalidField($path, 'is required and must be a string or null');
        }

        $value = $data[$field];
        if ($value !== null && is_string($value) === false) {
            throw InvalidApiRequest::invalidField($path, 'must be a string or null');
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    public function mapOptionalNullableString(array $data, string $field, string $path) : ?string
    {
        if (array_key_exists($field, $data) === false) {
            return null;
        }

        $value = $data[$field];
        if ($value !== null && is_string($value) === false) {
            throw InvalidApiRequest::invalidField($path, 'must be a string or null');
        }

        return $value;
    }

    public function mapLimit(Request $request) : int
    {
        $getParameters = $request->getGetParameters();

        $limit = $getParameters['limit'] ?? self::DEFAULT_LIMIT;

        return (int)$limit;
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
