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

    private const int MAX_LIMIT = 100;

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
    public function mapOptionalNullableDate(array $data, string $field, string $path) : ?Date
    {
        if (array_key_exists($field, $data) === false) {
            return null;
        }

        return $this->mapNullableDate($data[$field], $path);
    }

    public function mapNullableDate(mixed $value, string $path) : ?Date
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) === false) {
            throw InvalidApiRequest::invalidField($path, 'must be a date in YYYY-MM-DD format or null');
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw InvalidApiRequest::invalidField($path, 'must be a date in YYYY-MM-DD format or null');
        }

        return Date::createFromString($value);
    }

    /**
     * @param array<string, mixed> $data
     * @return list<mixed>
     */
    public function mapOptionalList(array $data, string $field, string $path) : array
    {
        if (array_key_exists($field, $data) === false || $data[$field] === null) {
            return [];
        }

        $value = $data[$field];
        if (is_array($value) === false || array_is_list($value) === false) {
            throw InvalidApiRequest::invalidField($path, 'must be an array');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     * @return list<array<string, mixed>>
     */
    public function mapOptionalObjectList(array $data, string $field, string $path) : array
    {
        $result = [];
        foreach ($this->mapOptionalList($data, $field, $path) as $index => $item) {
            if (is_array($item) === false || ($item !== [] && array_is_list($item) === true)) {
                throw InvalidApiRequest::invalidField($path . '[' . $index . ']', 'must be an object');
            }

            /** @var array<string, mixed> $item */
            $result[] = $item;
        }

        return $result;
    }

    /** @param array<string, mixed> $data */
    public function mapRequiredPositiveInteger(
        array $data,
        string $field,
        string $path,
        ?int $maximum = null,
    ) : int
    {
        $value = $data[$field] ?? null;
        if (is_int($value) === false || $value < 1) {
            throw InvalidApiRequest::invalidField($path, 'must be a positive integer');
        }
        if ($maximum !== null && $value > $maximum) {
            throw InvalidApiRequest::invalidField($path, 'must be at most ' . $maximum);
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    public function mapOptionalPositiveInteger(
        array $data,
        string $field,
        string $path,
        int $default,
        ?int $maximum = null,
    ) : int {
        if (array_key_exists($field, $data) === false || $data[$field] === null) {
            return $default;
        }

        return $this->mapRequiredPositiveInteger($data, $field, $path, $maximum);
    }

    /** @param array<string, mixed> $data */
    public function mapOptionalNullablePositiveInteger(
        array $data,
        string $field,
        string $path,
        ?int $maximum = null,
    ) : ?int {
        if (array_key_exists($field, $data) === false || $data[$field] === null) {
            return null;
        }

        return $this->mapRequiredPositiveInteger($data, $field, $path, $maximum);
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
        return $this->mapPositiveQueryInteger($request, 'limit', self::DEFAULT_LIMIT, self::MAX_LIMIT);
    }

    public function mapPage(Request $request) : int
    {
        return $this->mapPositiveQueryInteger($request, 'page', self::DEFAULT_PAGE);
    }

    public function mapReleaseYear(Request $request) : ?Year
    {
        $getParameters = $request->getGetParameters();

        if (array_key_exists('releaseYear', $getParameters) === false) {
            return null;
        }

        $releaseYear = $getParameters['releaseYear'];
        if (is_string($releaseYear) === false || preg_match('/^\d{4}$/D', $releaseYear) !== 1) {
            throw InvalidApiRequest::invalidField('releaseYear', 'must be a year between 1901 and 2155');
        }

        try {
            return Year::createFromString($releaseYear);
        } catch (RuntimeException) {
            throw InvalidApiRequest::invalidField('releaseYear', 'must be a year between 1901 and 2155');
        }
    }

    public function mapSearchTerm(Request $request) : ?string
    {
        $getParameters = $request->getGetParameters();

        if (array_key_exists('search', $getParameters) === false) {
            return null;
        }

        $searchTerm = $getParameters['search'];
        if (is_string($searchTerm) === false) {
            throw InvalidApiRequest::invalidField('search', 'must be a string');
        }

        return $searchTerm;
    }

    public function mapRequiredSearchTerm(Request $request) : string
    {
        $searchTerm = $this->mapSearchTerm($request);
        if ($searchTerm === null) {
            throw InvalidApiRequest::invalidField('search', 'is required and must be a string');
        }

        return $searchTerm;
    }

    /** @param list<string> $allowedValues */
    public function mapSortBy(Request $request, array $allowedValues, string $defaultSortBy) : string
    {
        $sortBy = $request->getGetParameters()['sortBy'] ?? $defaultSortBy;
        if (is_string($sortBy) === false || in_array($sortBy, $allowedValues, true) === false) {
            throw InvalidApiRequest::invalidField('sortBy', 'must be one of: ' . implode(', ', $allowedValues));
        }

        return $sortBy;
    }

    public function mapSortOrder(Request $request, ?string $defaultSortOrder = null) : SortOrder
    {
        $getParameters = $request->getGetParameters();

        $sortOrder = $getParameters['sortOrder'] ?? $defaultSortOrder ?? self::DEFAULT_SORT_ORDER;

        return match ($sortOrder) {
            'asc' => SortOrder::createAsc(),
            'desc' => SortOrder::createDesc(),

            default => throw InvalidApiRequest::invalidField('sortOrder', 'must be one of: asc, desc'),
        };
    }

    private function mapPositiveQueryInteger(
        Request $request,
        string $field,
        int $default,
        ?int $maximum = null,
    ) : int {
        $getParameters = $request->getGetParameters();
        if (array_key_exists($field, $getParameters) === false) {
            return $default;
        }

        $value = $getParameters[$field];
        $validatedValue = is_string($value) === true && preg_match('/^[1-9]\d*$/D', $value) === 1
            ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : $value;
        if (is_int($validatedValue) === false || $validatedValue < 1) {
            throw InvalidApiRequest::invalidField($field, 'must be a positive integer');
        }

        if ($maximum !== null && $validatedValue > $maximum) {
            throw InvalidApiRequest::invalidField($field, 'must be at most ' . $maximum);
        }

        return $validatedValue;
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
