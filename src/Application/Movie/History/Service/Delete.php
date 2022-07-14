<?php declare(strict_types=1);

namespace Movary\Application\Movie\History\Service;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Movie\History\Repository;
use Movary\ValueObject\Date;

class Delete
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function deleteByTraktId(TraktId $traktId) : void
    {
        $this->repository->deleteByTraktId($traktId);
    }

    public function deleteByUserId(int $userId) : void
    {
        $this->repository->deleteByUserId($userId);
    }

    public function deleteHistoryByIdAndDate(int $movieId, int $userId, Date $watchedAt) : void
    {
        $this->repository->deleteHistoryByIdAndDate($movieId, $userId, $watchedAt);
    }
}
