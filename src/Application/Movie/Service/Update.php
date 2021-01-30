<?php declare(strict_types=1);

namespace Movary\Application\Movie\Service;

use Movary\Application\Movie\Entity;
use Movary\Application\Movie\Repository;

class Update
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function updateRating(int $id, ?int $rating) : Entity
    {
        return $this->repository->updateRating($id, $rating);
    }
}
