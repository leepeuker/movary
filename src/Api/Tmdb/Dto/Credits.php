<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class Credits
{
    private function __construct(
        private readonly Cast $cast,
        private readonly Crew $crew
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            Cast::createFromArray($data['cast']),
            Crew::createFromArray($data['crew']),
        );
    }

    public function getCast() : Cast
    {
        return $this->cast;
    }

    public function getCrew() : Crew
    {
        return $this->crew;
    }
}
