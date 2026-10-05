<?php declare(strict_types=1);

namespace Movary\Service;

use InvalidArgumentException;
use Movary\ValueObject\PaginationElements;

class PaginationElementsCalculator
{
    public function createPaginationElements(int $totalCount, int $limit, int $currentPage) : PaginationElements
    {
        if ($totalCount < 0 || $limit < 1 || $currentPage < 1) {
            throw new InvalidArgumentException('Pagination values must be positive.');
        }

        $maxPage = max(1, (int)ceil($totalCount / $limit));
        $currentPage = min($currentPage, $maxPage);

        return PaginationElements::create(
            $currentPage,
            $maxPage,
            $currentPage > 1 ? $currentPage - 1 : null,
            $currentPage < $maxPage ? $currentPage + 1 : null,
            $totalCount,
            $limit,
        );
    }
}
