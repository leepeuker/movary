<?php

namespace Movary\HttpController\Web\Dto;

use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;

class MoviesRequestDto
{
    private function __construct(
        private readonly ?int $userId,
        private readonly ?string $searchTerm,
        private readonly int $page,
        private readonly int $limit,
        private readonly string $sortBy,
        private readonly SortOrder $sortOrder,
        private readonly ?Year $releaseYear,
        private readonly ?string $language,
        private readonly ?string $genre,
    ) {
    }

    public static function createFromParameters(
        ?int $userId,
        ?string $searchTerm,
        int $page,
        int $limit,
        string $sortBy,
        SortOrder $sortOrder,
        ?Year $releaseYear,
        ?string $language = null,
        ?string $genre = null,
    ) : self {
        return new self(
            $userId,
            $searchTerm,
            $page,
            $limit,
            $sortBy,
            $sortOrder,
            $releaseYear,
            $language,
            $genre,
        );
    }

    public function getGenre() : ?string
    {
        return $this->genre;
    }

    public function getLanguage() : ?string
    {
        return $this->language;
    }

    public function getLimit() : int
    {
        return $this->limit;
    }

    public function getPage() : int
    {
        return $this->page;
    }

    public function getReleaseYear() : ?Year
    {
        return $this->releaseYear;
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

    public function getUserId() : ?int
    {
        return $this->userId;
    }
}
