<?php declare(strict_types=1);

namespace Movary\Service;

use Movary\ValueObject\PaginationElements;

class PaginationElementsCalculator
{
    public function createPaginationElements(int $totalCount, int $limit, int $currentPage) : PaginationElements
    {
        $maxPage = (int)ceil($totalCount / $limit);

        return PaginationElements::create(
            $currentPage,
            $maxPage,
            $currentPage > 1 ? $currentPage - 1 : null,
            $currentPage < $maxPage ? $currentPage + 1 : null,
        );
    }
}
