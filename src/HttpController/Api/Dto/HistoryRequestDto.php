<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use Movary\ValueObject\SortOrder;

class HistoryRequestDto
{
    private function __construct(
        private readonly int $requestedUserId,
        private readonly ?string $searchTerm,
        private readonly int $page,
        private readonly int $limit,
        private readonly string $sortBy,
        private readonly SortOrder $sortOrder,
    ) {
    }

    public static function create(
        int $requestedUserId,
        ?string $searchTerm,
        int $page,
        int $limit,
        string $sortBy,
        SortOrder $sortOrder,
    ) : self {
        return new self(
            $requestedUserId,
            $searchTerm,
            $page,
            $limit,
            $sortBy,
            $sortOrder,
        );
    }

    public function getLimit() : int
    {
        return $this->limit;
    }

    public function getPage() : int
    {
        return $this->page;
    }

    public function getRequestedUserId() : int
    {
        return $this->requestedUserId;
    }

    public function getSearchTerm() : ?string
    {
        return $this->searchTerm;
    }

    public function getSortBy() : string
    {
        return $this->sortBy;
    }

    public function getSortOrder() : SortOrder
    {
        return $this->sortOrder;
    }
}
