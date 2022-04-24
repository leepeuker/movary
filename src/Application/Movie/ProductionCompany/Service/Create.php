<?php declare(strict_types=1);

namespace Movary\Application\Movie\ProductionCompany\Service;

use Movary\Application\Movie\ProductionCompany\Repository;

class Create
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function create(int $movieId, int $genreId, int $position) : void
    {
        $this->repository->create($movieId, $genreId, $position);
    }
}
