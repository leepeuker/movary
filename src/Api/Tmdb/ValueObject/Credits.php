<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\ValueObject;

class Credits
{
    private Cast $cast;

    private Crew $crew;

    private function __construct(Cast $cast, Crew $crew)
    {
        $this->cast = $cast;
        $this->crew = $crew;
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
