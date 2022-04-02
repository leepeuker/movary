<?php declare(strict_types=1);

namespace Movary\Application\Person\Service;

use Movary\Application\Person\Entity;
use Movary\Application\Person\Repository;
use Movary\ValueObject\Gender;

class Create
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function create(string $name, Gender $gender, ?float $popularity, ?string $knownForDepartment, int $tmdbId) : Entity
    {
        return $this->repository->create($name, $gender, $popularity, $knownForDepartment, $tmdbId);
    }
}
