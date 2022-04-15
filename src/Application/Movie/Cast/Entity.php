<?php declare(strict_types=1);

namespace Movary\Application\Movie\Cast;

class Entity
{
    private string $character;

    private int $movieId;

    private int $personId;

    private int $position;

    private function __construct(int $movieId, int $personId, string $character, int $position)
    {
        $this->movieId = $movieId;
        $this->personId = $personId;
        $this->character = $character;
        $this->position = $position;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['movie_id'],
            (int)$data['person_id'],
            $data['character'],
            (int)$data['position'],
        );
    }

    public function getCharacter() : string
    {
        return $this->character;
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
