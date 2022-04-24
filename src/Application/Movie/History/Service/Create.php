<?php declare(strict_types=1);

namespace Movary\Application\Movie\History\Service;

use Movary\Application\Movie\History\Repository;
use Movary\ValueObject\Date;

class Create
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function create(int $movieId, Date $watchedAt) : void
    {
        $this->repository->create($movieId, $watchedAt);
    }
}
