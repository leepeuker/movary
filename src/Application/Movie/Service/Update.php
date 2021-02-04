<?php declare(strict_types=1);

namespace Movary\Application\Movie\Service;

use Movary\Application\Movie\Entity;
use Movary\Application\Movie\Repository;
use Movary\ValueObject\DateTime;

class Update
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
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

    public function updateRating(int $id, ?int $rating) : Entity
    {
        return $this->repository->updateRating($id, $rating);
    }
}
