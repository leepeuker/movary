<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;

class PlayedRequestDto
{
    private function __construct(
        private readonly int $requestedUserId,
        private readonly ?string $searchTerm,
        private readonly int $page,
        private readonly int $limit,
        private readonly string $sortBy,
        private readonly SortOrder $sortOrder,
        private readonly ?Year $releaseYear,
        private readonly ?string $language,
        private readonly ?string $genre,
        private readonly ?int $locationId,
    ) {
    }

    public static function create(
        int $requestedUserId,
        ?string $searchTerm,
        int $page,
        int $limit,
        string $sortBy,
        SortOrder $sortOrder,
        ?Year $releaseYear = null,
        ?string $language = null,
        ?string $genre = null,
        ?int $locationId = null,
    ) : self {
        return new self(
            $requestedUserId,
            $searchTerm,
            $page,
            $limit,
            $sortBy,
            $sortOrder,
            $releaseYear,
            $language,
            $genre,
            $locationId,
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

    public function getLocationId() : ?int
    {
        return $this->locationId;
    }

    public function getPage() : int
    {
        return $this->page;
    }

    public function getReleaseYear() : ?Year
    {
        return $this->releaseYear;
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
