<?php declare(strict_types=1);

namespace Movary\Application\Person\Service;

use Movary\Application\Person\Repository;
use Movary\ValueObject\Gender;

class Update
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function update(int $id, string $name, Gender $gender, ?string $knownForDepartment, int $tmdbId, ?string $posterPath) : void
    {
        $this->repository->update($id, $name, $gender, $knownForDepartment, $tmdbId, $posterPath);
    }
}
