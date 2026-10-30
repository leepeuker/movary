<?php declare(strict_types=1);

namespace Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\ValueObject\Gender;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;

class PersonListRequestMapper
{
    public function __construct(private readonly RequestMapper $requestMapper)
    {
    }

    /** @return array{page: int, limit: int, search: ?string, gender: ?Gender, role: ?string, sortBy: string, sortOrder: SortOrder} */
    public function mapRequest(Request $request) : array
    {
        $query = $request->getGetParameters();
        $gender = $query['gender'] ?? null;
        if ($gender !== null && (is_string($gender) === false || in_array($gender, ['0', '1', '2', '3'], true) === false)) {
            throw InvalidApiRequest::invalidField('gender', 'must be 0, 1, 2, or 3');
        }

        $role = $query['role'] ?? null;
        if ($role !== null && (is_string($role) === false || in_array($role, ['actor', 'director'], true) === false)) {
            throw InvalidApiRequest::invalidField('role', 'must be actor or director');
        }

        return [
            'page' => $this->requestMapper->mapPage($request),
            'limit' => $this->requestMapper->mapLimit($request),
            'search' => $this->requestMapper->mapSearchTerm($request),
            'gender' => $gender === null ? null : Gender::createFromInt((int)$gender),
            'role' => $role,
            'sortBy' => $this->requestMapper->mapSortBy($request, ['id', 'name', 'birthDate'], 'name'),
            'sortOrder' => $this->requestMapper->mapSortOrder($request, 'asc'),
        ];
    }
}
