<?php declare(strict_types=1);

namespace Movary\Application\Movie\Cast\Service;

use Movary\Application\Movie\Cast\Repository;

class Create
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function create(int $movieId, int $personId, string $character, int $position) : void
    {
        $this->repository->create($movieId, $personId, $character, $position);
    }
}
