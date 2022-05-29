<?php declare(strict_types=1);

namespace Movary\Application\Movie\Crew\Service;

use Movary\Application\Movie\Crew\Repository;
use Movary\Application\Person;

class Select
{
    public function __construct(private readonly Repository $repository, private readonly Person\Service\Select $personSelectService)
    {
    }

    public function findDirectorsByMovieId(int $movieId) : ?array
    {
        $directors = [];

        foreach ($this->repository->findDirectorsByMovieId($movieId) as $director) {
            $person = $this->personSelectService->findById($director->getPersonId());

            $directors[] = [
                'name' => $person?->getName(),
                'posterPath' => $person?->getPosterPath(),
            ];
        }

        return $directors;
    }
}
