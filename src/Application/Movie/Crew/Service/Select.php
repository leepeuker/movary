<?php declare(strict_types=1);

namespace Movary\Application\Movie\Crew\Service;

use Movary\Application\Movie\Crew\Repository;
use Movary\Application\Person;

class Select
{
    public function __construct(private readonly Repository $repository, private readonly Person\Api $personApi)
    {
    }

    public function findDirectorsByMovieId(int $movieId) : ?array
    {
        $directors = [];

        foreach ($this->repository->findDirectorsByMovieId($movieId) as $director) {
            $person = $this->personApi->findById($director->getPersonId());

            $directors[] = [
                'id' => $person?->getId(),
                'name' => $person?->getName(),
                'tmdbPosterPath' => $person?->getTmdbPosterPath(),
            ];
        }

        return $directors;
    }
}
