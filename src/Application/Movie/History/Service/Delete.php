<?php declare(strict_types=1);

namespace Movary\Application\Movie\History\Service;

use Movary\Application\Movie\History\Repository;

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
