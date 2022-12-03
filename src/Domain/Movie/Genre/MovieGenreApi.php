<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Genre;

use Movary\Domain\Genre;

class MovieGenreApi
{
    public function __construct(
        private readonly MovieGenreRepository $repository,
        private readonly Genre\GenreApi $genreApi,
    ) {
    }

    public function create(int $movieId, int $genreId, int $position) : void
    {
        $this->repository->create($movieId, $genreId, $position);
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->repository->deleteByMovieId($movieId);
    }

    public function findByMovieId(int $movieId) : ?array
    {
        $movieGenres = [];

        foreach ($this->repository->findByMovieId($movieId) as $movieGenre) {
            $movieGenres[] = ['name' => $this->genreApi->findById($movieGenre->getGenreId())?->getName()];
        }

        return $movieGenres;
    }
}
