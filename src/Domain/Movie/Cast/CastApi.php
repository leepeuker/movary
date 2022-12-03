<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Cast;

use Movary\Domain\Person\PersonApi;
use Movary\Service\UrlGenerator;

class CastApi
{
    public function __construct(
        private readonly CastRepository $repository,
        private readonly PersonApi $personApi,
        private readonly UrlGenerator $urlGenerator,
    ) {
    }

    public function create(int $movieId, int $personId, string $character, int $position) : void
    {
        $this->repository->create($movieId, $personId, $character, $position);
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->repository->deleteByMovieId($movieId);
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
