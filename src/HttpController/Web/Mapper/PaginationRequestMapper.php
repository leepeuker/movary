<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Mapper;

use Movary\ValueObject\Http\Request;
use Movary\ValueObject\PaginationRequest;

class PaginationRequestMapper
{
    /**
     * @param array<int, int> $allowedPerPageValues
     * @param array<int, string> $legacyPerPageParameters
     */
    public function map(
        Request $request,
        int $defaultPerPage,
        array $allowedPerPageValues,
        array $legacyPerPageParameters = [],
    ) : PaginationRequest {
        $parameters = $request->getGetParameters();

        $page = $this->mapPositiveInteger($parameters['page'] ?? $parameters['p'] ?? null, 1);
        $perPageValue = $parameters['perPage'] ?? null;

        foreach ($legacyPerPageParameters as $legacyParameter) {
            if ($perPageValue !== null) {
                break;
            }

            $perPageValue = $parameters[$legacyParameter] ?? null;
        }

        $perPage = $this->mapPositiveInteger($perPageValue, $defaultPerPage);
        if (in_array($perPage, $allowedPerPageValues, true) === false) {
            $perPage = $defaultPerPage;
        }

        return PaginationRequest::create($page, $perPage);
    }

    private function mapPositiveInteger(mixed $value, int $default) : int
    {
        if (is_int($value) === true && $value > 0) {
            return $value;
        }

        if (is_string($value) === false || ctype_digit($value) === false || (int)$value < 1) {
            return $default;
        }

        return (int)$value;
    }
}
