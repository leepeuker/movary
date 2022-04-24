<?php declare(strict_types=1);

namespace Movary\Application\Movie\Genre\Service;

use Movary\Application\Movie\Genre\Repository;

class Delete
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->repository->deleteByMovieId($movieId);
    }
}
