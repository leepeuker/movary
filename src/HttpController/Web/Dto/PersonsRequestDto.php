<?php

namespace Movary\HttpController\Web\Dto;

use Movary\ValueObject\Gender;
use Movary\ValueObject\SortOrder;

class PersonsRequestDto
{
    private function __construct(
        private readonly int $userId,
        private readonly ?string $searchTerm,
        private readonly int $page,
        private readonly int $limit,
        private readonly string $sortBy,
        private readonly SortOrder $sortOrder,
        private readonly ?Gender $gender,
    ) {
    }

    public static function createFromParameters(
        int $userId,
        ?string $searchTerm,
        int $page,
        int $limit,
        string $sortBy,
        SortOrder $sortOrder,
        ?Gender $gender,
    ) : self {
        return new self(
            $userId,
            $searchTerm,
            $page,
            $limit,
            $sortBy,
            $sortOrder,
            $gender,
        );
    }

    public function getGender() : ?Gender
    {
        return $this->gender;
    }

    public function getLimit() : int
    {
        return $this->limit;
    }

    public function getPage() : int
    {
        return $this->page;
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

    public function getUserId() : int
    {
        return $this->userId;
    }
}
