<?php declare(strict_types=1);

namespace Movary\Application\Movie\Service;

use Matriphe\ISO639\ISO639;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Movie\Entity;
use Movary\Application\Movie\EntityList;
use Movary\Application\Movie\Repository;

class Select
{
    public function __construct(private readonly Repository $repository, private readonly ISO639 $ISO639)
    {
    }

    public function fetchAll() : EntityList
    {
        return $this->repository->fetchAll();
    }

    public function fetchAllOrderedByLastUpdatedAtTmdbDesc() : EntityList
    {
        return $this->repository->fetchAllOrderedByLastUpdatedAtTmdbDesc();
    }

    public function findById(int $movieId) : ?array
    {
        $entity = $this->repository->findById($movieId);

        if ($entity === null) {
            return null;
        }

        return [
            'title' => $entity->getTitle(),
            'releaseDate' => $entity->getReleaseDate(),
            'posterPath' => $entity->getPosterPath(),
            'rating5' => $entity->getRating5(),
            'rating10' => $entity->getRating10(),
            'tagline' => $entity->getTagline(),
            'overview' => $entity->getOverview(),
            'originalLanguage' => $this->ISO639->languageByCode1($entity->getOriginalLanguage()),
        ];
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
}
