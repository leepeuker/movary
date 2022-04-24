<?php declare(strict_types=1);

namespace Movary\Api\Trakt\Cache\User\Movie\Rating;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Api\Trakt\ValueObject\User\Movie\Rating\Dto;
use Movary\Api\Trakt\ValueObject\User\Movie\Rating\DtoList;

class Service
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function findRatingByTraktId(TraktId $traktId) : ?int
    {
        return $this->repository->findByTraktId($traktId);
    }

    public function set(DtoList $ratings) : void
    {
        $this->repository->clearCache();

        /** @var Dto $rating */
        foreach ($ratings as $rating) {
            $this->repository->create($rating->getMovie()->getTraktId(), $rating->getRating(), $rating->getRatedAt());
        }
    }
}
