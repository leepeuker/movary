<?php declare(strict_types=1);

namespace Movary\Application\Movie\Cast\Service;

use Movary\Application\Movie\Cast\Repository;

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
