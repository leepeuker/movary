<?php declare(strict_types=1);

namespace Movary\Application\Service\Trakt;

use Movary\Api;
use Movary\Application;
use Movary\ValueObject\PersonalRating;

class SyncRatings
{
    public function __construct(
        private readonly Application\Movie\Api $movieApi,
        private readonly Api\Trakt\Api $traktApi,
        private readonly Api\Trakt\Cache\User\Movie\Rating\Service $traktApiCacheUserMovieRatingService,
        private readonly Application\SyncLog\Repository $scanLogRepository
    ) {
    }

    public function execute(int $userId, bool $overwriteExistingData = false) : void
    {
        $this->traktApiCacheUserMovieRatingService->set($userId, $this->traktApi->fetchUserMoviesRatings());

        foreach ($this->movieApi->fetchAll() as $movie) {
            $traktId = $movie->getTraktId();

            if ($traktId === null) {
                continue;
            }

            $traktUserRating = $this->traktApiCacheUserMovieRatingService->findRatingByTraktId($userId, $traktId);

            if ($traktUserRating === null) {
                continue;
            }

            $userRating = $this->movieApi->findUserRating($movie->getId(), $userId);

            if ($overwriteExistingData === true || $userRating === null) {
                $this->movieApi->updateUserRating($movie->getId(), $userId, PersonalRating::create($traktUserRating));
            }
        }

        $this->scanLogRepository->insertLogForTraktSync();
    }
}
