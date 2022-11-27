<?php declare(strict_types=1);

namespace Movary\Application\Movie\Cast\Service;

use Movary\Application\Movie\Cast\Repository;
use Movary\Application\Person;
use Movary\Application\Service\UrlGenerator;

class Select
{
    public function __construct(
        private readonly Repository $repository,
        private readonly Person\Api $personApi,
        private readonly UrlGenerator $urlGenerator,
    ) {
    }

    public function findByMovieId(int $movieId) : ?array
    {
        $castMembers = [];

        foreach ($this->repository->findByMovieId($movieId) as $movieGenre) {
            $person = $this->personApi->findById($movieGenre->getPersonId());

            $castMembers[] = [
                'id' => $person?->getId(),
                'name' => $person?->getName(),
                'posterPath' => $this->urlGenerator->generateImageSrcUrlFromParameters($person?->getTmdbPosterPath(), $person?->getPosterPath()),
            ];
        }

        return $castMembers;
    }
}
