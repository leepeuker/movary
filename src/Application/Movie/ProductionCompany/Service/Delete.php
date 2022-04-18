<?php declare(strict_types=1);

namespace Movary\Application\Movie\ProductionCompany\Service;

use Movary\Application\Movie\ProductionCompany\Repository;

class Delete
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->repository->deleteByMovieId($movieId);
    }
}
