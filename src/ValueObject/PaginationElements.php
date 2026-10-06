<?php declare(strict_types=1);

namespace Movary\ValueObject;

class PaginationElements
{
    private function __construct(
        private readonly int $currentPage,
        private readonly int $maxPage,
        private readonly ?int $previous,
        private readonly ?int $next,
        private readonly int $totalCount,
        private readonly int $limit,
    ) {
    }

    public static function create(
        int $currentPage,
        int $maxPage,
        ?int $previous,
        ?int $next,
        int $totalCount = 0,
        int $limit = 1,
    ) : self {
        return new self($currentPage, $maxPage, $previous, $next, $totalCount, $limit);
    }

    public function getCurrentPage() : int
    {
        return $this->currentPage;
    }

    public function getMaxPage() : int
    {
        return $this->maxPage;
    }

    public function getOffset() : int
    {
        return ($this->currentPage - 1) * $this->limit;
    }

    public function getNext() : ?int
    {
        return $this->next;
    }

    public function getPrevious() : ?int
    {
        return $this->previous;
    }

    public function getTotalCount() : int
    {
        return $this->totalCount;
    }
}
