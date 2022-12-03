<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Cast;

class CastEntity
{
    private function __construct(
        private readonly int $movieId,
        private readonly int $personId,
        private readonly ?string $character,
        private readonly int $position
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['movie_id'],
            (int)$data['person_id'],
            $data['character'] ?? null,
            (int)$data['position'],
        );
    }

    public function getCharacter() : ?string
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
