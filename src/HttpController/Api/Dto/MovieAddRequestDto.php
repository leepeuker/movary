<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

class MovieAddRequestDto
{
    private function __construct(private readonly int $tmdbId)
    {
    }

    public static function create(int $tmdbId) : self
    {
        return new self($tmdbId);
    }

    public function getTmdbId() : int
    {
        return $this->tmdbId;
    }
}
