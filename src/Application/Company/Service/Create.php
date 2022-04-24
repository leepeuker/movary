<?php declare(strict_types=1);

namespace Movary\Application\Company\Service;

use Movary\Application\Company\Entity;
use Movary\Application\Company\Repository;

class Create
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function create(string $name, ?string $originCountry, int $tmdbId) : Entity
    {
        return $this->repository->create($name, $originCountry, $tmdbId);
    }
}
