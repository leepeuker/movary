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

    public function createOrUpdatePlaysForDate(int $movieId, int $userId, Date $watchedAt, int $plays) : void
    {
        $this->repository->createOrUpdatePlaysForDate($movieId, $userId, $watchedAt, $plays);
    }
}
