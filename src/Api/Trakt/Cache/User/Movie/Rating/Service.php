<?php declare(strict_types=1);

namespace Movary\Api\Trakt\Cache\User\Movie\Rating;

use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\Api\Trakt\ValueObject\User\Movie\Rating\Dto;
use Movary\Api\Trakt\ValueObject\User\Movie\Rating\DtoList;

class Service
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function findRatingByTraktId(int $userId, TraktId $traktId) : ?int
    {
        return $this->repository->findByTraktId($userId, $traktId);
    }

    public function set(int $userId, DtoList $ratings) : void
    {
        $this->repository->clearCache($userId);

        /** @var Dto $rating */
        foreach ($ratings as $rating) {
            $this->repository->create($userId, $rating->getMovie()->getTraktId(), $rating->getRating(), $rating->getRatedAt());
        }
    }
}
