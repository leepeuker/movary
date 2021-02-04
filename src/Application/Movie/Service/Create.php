<?php declare(strict_types=1);

namespace Movary\Application\Movie\Service;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Movie\Entity;
use Movary\Application\Movie\Repository;
use Movary\ValueObject\Year;

class Create
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function create(string $title, ?int $rating, TraktId $traktId, string $imdbId, int $tmdbId) : Entity
    {
        return $this->repository->create($title, $rating, $traktId, $imdbId, $tmdbId);
    }
}
