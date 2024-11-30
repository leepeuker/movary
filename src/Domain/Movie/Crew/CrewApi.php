<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Crew;

use Movary\Domain\Person\PersonApi;
use Movary\Service\UrlGenerator;

class CrewApi
{
    private const array RELEVANT_JOBS = [
        'Screenplay',
        'Director',
        'Producer',
        'Story',
        'Executive Producer',
        'Director of Photography',
        'Editor',
        'Director of Photography',
        'Original Music Composer',
        'Author',
        'Music Director',
    ];

    public function __construct(
        private readonly CrewRepository $repository,
        private readonly PersonApi $personApi,
        private readonly UrlGenerator $urlGenerator,
    ) {
    }

    public function create(int $movieId, int $personId, string $job, string $department, int $position) : void
    {
        if (in_array($job, self::RELEVANT_JOBS, true) === false) {
            return;
        }

        $this->repository->create($movieId, $personId, $job, $department, $position);
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->repository->deleteByMovieId($movieId);
    }

    public function findDirectorsByMovieId(int $movieId) : ?array
    {
        $directors = [];

        foreach ($this->repository->findDirectorsByMovieId($movieId) as $director) {
            $person = $this->personApi->findById($director->getPersonId());

            $directors[] = [
                'id' => $person?->getId(),
                'name' => $person?->getName(),
                'posterPath' => $this->urlGenerator->generateImageSrcUrlFromParameters($person?->getTmdbPosterPath(), $person?->getPosterPath()),
            ];
        }

        return $directors;
    }
}
