<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use Movary\ValueObject\Year;

class SearchRequestDto
{
    private function __construct(
        private readonly string $searchTerm,
        private readonly int $page,
        private readonly ?Year $year,
    ) {
    }

    public static function create(
        string $searchTerm,
        int $page,
        ?Year $year,
    ) : self {
        return new self(
            $searchTerm,
            $page,
            $year,
        );
    }

    public function getPage() : int
    {
        return $this->page;
    }

    public function getSearchTerm() : string
    {
        return $this->searchTerm;
    }

    public function getYear() : ?Year
    {
        return $this->year;
    }
}
