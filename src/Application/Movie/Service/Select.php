<?php declare(strict_types=1);

namespace Movary\Application\Movie\Service;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Movie\Entity;
use Movary\Application\Movie\EntityList;
use Movary\Application\Movie\Repository;
use Movary\Application\Service\UrlGenerator;
use Movary\ValueObject\PersonalRating;

class Select
{
    public function __construct(
        private readonly Repository $repository,
        private readonly UrlGenerator $urlGenerator,
    ) {
    }

    public function fetchAll() : EntityList
    {
        return $this->repository->fetchAll();
    }

    public function fetchWithActor(int $personId, int $userId) : array
    {
        $movies = $this->repository->fetchWithActor($personId, $userId);

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($movies);
    }

    public function fetchWithDirector(int $personId, int $userId) : array
    {
        $movies = $this->repository->fetchWithDirector($personId, $userId);

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($movies);
    }

    public function findById(int $movieId) : ?Entity
    {
        return $this->repository->findById($movieId);
    }

    public function findByLetterboxdId(string $letterboxdId) : ?Entity
    {
        return $this->repository->findByLetterboxdId($letterboxdId);
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        return $this->repository->findByTmdbId($tmdbId);
    }

    public function findByTraktId(TraktId $traktId) : ?Entity
    {
        return $this->repository->findByTraktId($traktId);
    }

    public function findUserRating(int $movieId, int $userId) : ?PersonalRating
    {
        return $this->repository->findUserRating($movieId, $userId);
    }
}
