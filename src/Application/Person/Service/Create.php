<?php declare(strict_types=1);

namespace Movary\Application\Person\Service;

use Movary\Application\Person\Entity;
use Movary\Application\Person\Repository;
use Movary\ValueObject\Gender;

class Create
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function create(string $name, Gender $gender, ?string $knownForDepartment, int $tmdbId, ?string $posterPath) : Entity
    {
        return $this->repository->create($name, $gender, $knownForDepartment, $tmdbId, $posterPath);
    }
}
