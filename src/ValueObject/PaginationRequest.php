<?php declare(strict_types=1);

namespace Movary\ValueObject;

class PaginationRequest
{
    private function __construct(
        private readonly int $page,
        private readonly int $perPage,
    ) {
    }

    public static function create(int $page, int $perPage) : self
    {
        return new self($page, $perPage);
    }

    public function getPage() : int
    {
        return $this->page;
    }

    public function getPerPage() : int
    {
        return $this->perPage;
    }
}
