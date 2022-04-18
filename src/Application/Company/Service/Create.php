<?php declare(strict_types=1);

namespace Movary\Application\Company\Service;

use Movary\Application\Company\Entity;
use Movary\Application\Company\Repository;

class Create
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function create(string $name, ?string $originCountry, int $tmdbId) : Entity
    {
        return $this->repository->create($name, $originCountry, $tmdbId);
    }
}
