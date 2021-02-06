<?php declare(strict_types=1);

namespace Movary\Application\Movie\Service;

use Movary\Application\Genre;
use Movary\Application\Movie;
use Movary\Application\Movie\Entity;
use Movary\Application\Movie\Repository;
use Movary\ValueObject\DateTime;

class Update
{
    private Movie\Genre\Service\Create $movieGenreCreateService;

    private Movie\Genre\Service\Delete $movieGenreDeleteService;

    private Repository $repository;

    public function __construct(
        Repository $repository,
        Movie\Genre\Service\Create $movieGenreCreateService,
        Movie\Genre\Service\Delete $movieGenreDeleteService
    ) {
        $this->repository = $repository;
        $this->movieGenreCreateService = $movieGenreCreateService;
        $this->movieGenreDeleteService = $movieGenreDeleteService;
    }

    public function updateDetails(
        int $id,
        ?string $overview,
        ?string $originalLanguage,
        ?DateTime $releaseDate,
        ?int $runtime,
        ?float $tmdbVoteAverage,
        ?int $tmdbVoteCount
    ) : Entity {
        return $this->repository->updateDetails($id, $overview, $originalLanguage, $releaseDate, $runtime, $tmdbVoteAverage, $tmdbVoteCount);
    }

    public function updateGenres(int $movieId, Genre\EntityList $genres) : void
    {
        $this->movieGenreDeleteService->deleteByMovieId($movieId);

        foreach ($genres as $position => $genre) {
            $this->movieGenreCreateService->create($movieId, $genre->getId(), (int)$position);
        }
    }

    public function updateRating(int $id, ?int $rating) : Entity
    {
        return $this->repository->updateRating($id, $rating);
    }
}
