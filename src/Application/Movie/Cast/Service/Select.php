<?php declare(strict_types=1);

namespace Movary\Application\Movie\Cast\Service;

use Movary\Application\Movie\Cast\Repository;
use Movary\Application\Person;

class Select
{
    public function __construct(private readonly Repository $repository, private readonly Person\Service\Select $personSelectService)
    {
    }

    public function findByMovieId(int $movieId) : ?array
    {
        $castMembers = [];

        foreach ($this->repository->findByMovieId($movieId) as $movieGenre) {
            $person = $this->personSelectService->findById($movieGenre->getPersonId());

            $castMembers[] = [
                'id' => $person?->getId(),
                'name' => $person?->getName(),
                'posterPath' => $person?->getPosterPath(),
            ];
        }

        return $castMembers;
    }
}
