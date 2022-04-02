<?php declare(strict_types=1);

namespace Movary\Application\Movie\Genre;

class Entity
{
    private int $genreId;

    private int $movieId;

    private int $position;

    private function __construct(int $movieId, int $genreId, int $position)
    {
        $this->movieId = $movieId;
        $this->genreId = $genreId;
        $this->position = $position;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['genre_id'],
            (int)$data['movie_id'],
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
