<?php declare(strict_types=1);

namespace Movary\Application\Movie\History\Service;

use Movary\Application\Movie\Entity;
use Movary\Application\Movie\History\Repository;
use Movary\ValueObject\DateTime;

class Create
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function create(int $movieId, DateTime $watchedAt) : void
    {
        $this->repository->create($movieId, $watchedAt);
    }
}
