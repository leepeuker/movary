<?php declare(strict_types=1);

namespace Movary\ValueObject;

class PaginationElements
{
    private function __construct(
        private readonly int $currentPage,
        private readonly int $maxPage,
        private readonly ?int $previous,
        private readonly ?int $next,
    ) {
    }

    public static function create(int $currentPage, int $maxPage, ?int $previous, ?int $next) : self
    {
        return new self($currentPage, $maxPage, $previous, $next);
    }

    public function getCurrentPage() : int
    {
        return $this->currentPage;
    }

    public function getMaxPage() : int
    {
        return $this->maxPage;
    }

    public function getNext() : ?int
    {
        return $this->next;
    }

    public function getPrevious() : ?int
    {
        return $this->previous;
    }
}
