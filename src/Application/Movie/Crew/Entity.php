<?php declare(strict_types=1);

namespace Movary\Application\Movie\Crew;

class Entity
{
    private string $job;

    private int $movieId;

    private int $personId;

    private int $position;

    private function __construct(int $movieId, int $personId, string $job, int $position)
    {
        $this->movieId = $movieId;
        $this->personId = $personId;
        $this->job = $job;
        $this->position = $position;
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
