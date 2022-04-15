<?php declare(strict_types=1);

namespace Movary\Application\Movie\Cast\Service;

use Movary\Application\Movie\Cast\Repository;

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
