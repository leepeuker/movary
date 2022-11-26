<?php

namespace Movary\HttpController\Dto;

use Movary\ValueObject\Year;

class MoviesRequestDto
{
    private function __construct(
        private readonly ?int $userId,
        private readonly ?string $searchTerm,
        private readonly int $page,
        private readonly int $limit,
        private readonly string $sortBy,
        private readonly string $sortOrder,
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
        string $sortOrder,
        ?Year $releaseYear,
        ?string $language,
        ?string $genre,
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

    public function getSortOrder() : string
    {
        return $this->sortOrder;
    }

    public function getUserId() : ?int
    {
        return $this->userId;
    }
}
