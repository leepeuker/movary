<?php declare(strict_types=1);

namespace Movary\Application\Service\Trakt;

use Movary\Api;
use Movary\Application;

class SyncRatings
{
    public function __construct(
        private readonly Application\Movie\Api $movieApi,
        private readonly Api\Trakt\Api $traktApi,
        private readonly Api\Trakt\Cache\User\Movie\Rating\Service $traktApiCacheUserMovieRatingService
    ) {
    }

    public function execute(bool $overwriteExistingData = false) : void
    {
        $this->traktApiCacheUserMovieRatingService->set($this->traktApi->fetchUserMoviesRatings());

        foreach ($this->movieApi->fetchAll() as $movie) {
            $traktId = $movie->getTraktId();

            if ($traktId === null) {
                continue;
            }

            $rating = $this->traktApiCacheUserMovieRatingService->findRatingByTraktId($traktId);

            if ($rating === null) {
                continue;
            }

            if ($overwriteExistingData === true || $movie->getRating10() === null) {
                $this->movieApi->updateRating10($movie->getId(), $rating);
            }
        }
    }
}
