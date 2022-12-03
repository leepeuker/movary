<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Genre;

class MovieGenreEntity
{
    private function __construct(
        private readonly int $movieId,
        private readonly int $genreId,
        private readonly int $position
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['movie_id'],
            (int)$data['genre_id'],
            (int)$data['position'],
        );
    }

    public function getGenreId() : int
    {
        return $this->genreId;
    }

    public function getMovieId() : int
    {
        return $this->movieId;
    }

    public function getPosition() : int
    {
        return $this->position;
    }
}
