<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Mapper;

use Movary\Domain\User\UserApi;
use Movary\HttpController\Web\Dto\PersonsRequestDto;
use Movary\Service\ListPagePreference;
use Movary\ValueObject\Gender;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;
use RuntimeException;

class PersonsRequestMapper
{
    private const array SORT_BY_VALUES = ['uniqueAppearances', 'totalAppearances', 'name'];

    private const int DEFAULT_LIMIT = 24;

    private const string DEFAULT_SORT_BY = 'uniqueAppearances';

    public function __construct(
        private readonly UserApi $userApi,
        private readonly ListPagePreference $listPagePreference,
    ) {
    }

    public function mapRenderPageRequest(Request $request, string $preferencePage) : PersonsRequestDto
    {
        $userId = $this->userApi->fetchUserByName((string)$request->getRouteParameters()['username'])->getId();

        $getParameters = $request->getGetParameters();
        $savedPreference = $this->listPagePreference->find($request, $preferencePage, self::SORT_BY_VALUES, ['ge']);
        $savedFilters = $savedPreference['filters'] ?? [];
        $filterParameters = [...$savedFilters, ...$getParameters];

        $searchTerm = $getParameters['s'] ?? null;
        $page = $getParameters['p'] ?? 1;
        $limit = $getParameters['pp'] ?? $savedPreference['perPage'] ?? self::DEFAULT_LIMIT;
        $sortBy = $getParameters['sb'] ?? $savedPreference['sortBy'] ?? self::DEFAULT_SORT_BY;
        $sortOrder = $this->mapSortOrder(
            $getParameters,
            $savedPreference['sortOrder'] ?? SortOrder::createDesc(),
        );
        $genderValue = $filterParameters['ge'] ?? null;
        $gender = $genderValue === null || $genderValue === '' ? null : Gender::createFromInt((int)$genderValue);

        return PersonsRequestDto::createFromParameters(
            $userId,
            $searchTerm,
            (int)$page,
            (int)$limit,
            $sortBy,
            $sortOrder,
            $gender,
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
