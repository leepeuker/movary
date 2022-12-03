<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class TmdbCredits
{
    private function __construct(
        private readonly TmdbCast $cast,
        private readonly TmdbCrew $crew
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            TmdbCast::createFromArray($data['cast']),
            TmdbCrew::createFromArray($data['crew']),
        );
    }

    public function getCast() : TmdbCast
    {
        return $this->cast;
    }

    public function getCrew() : TmdbCrew
    {
        return $this->crew;
    }
}
