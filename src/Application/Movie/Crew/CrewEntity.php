<?php declare(strict_types=1);

namespace Movary\Application\Movie\Crew;

class CrewEntity
{
    private function __construct(
        private readonly int $movieId,
        private readonly int $personId,
        private readonly string $job,
        private readonly int $position
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['movie_id'],
            (int)$data['person_id'],
            $data['job'],
            (int)$data['position'],
        );
    }

    public function getJob() : string
    {
        return $this->job;
    }

    public function getMovieId() : int
    {
        return $this->movieId;
    }

    public function getPersonId() : int
    {
        return $this->personId;
    }

    public function getPosition() : int
    {
        return $this->position;
    }
}
