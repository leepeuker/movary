<?php declare(strict_types=1);

namespace Movary\Application\Movie\Genre\Service;

use Movary\Application\Genre;
use Movary\Application\Movie\Genre\Repository;

class Select
{
    public function __construct(private readonly Repository $repository, private readonly Genre\Service\Select $genreSelectService)
    {
    }

    public function findByMovieId(int $movieId) : ?array
    {
        $movieGenres = [];

        foreach ($this->repository->findByMovieId($movieId) as $movieGenre) {
            $movieGenres[] = ['name' => $this->genreSelectService->findById($movieGenre->getGenreId())?->getName()];
        }

        return $movieGenres;
    }
}
