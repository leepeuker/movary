<?php declare(strict_types=1);

namespace Movary\Command\Trakt;

use Movary\Api;
use Movary\Application;

class SyncRatings
{
    private Application\Movie\Service\Select $movieSelectService;

    private Application\Movie\Service\Update $movieUpdateService;

    private Api\Trakt\Api $traktApi;

    private Api\Trakt\Cache\User\Movie\Rating\Service $traktApiCacheUserMovieRatingService;

    public function __construct(
        Application\Movie\Service\Update $movieUpdateService,
        Application\Movie\Service\Select $movieSelectService,
        Api\Trakt\Api $traktApi,
        Api\Trakt\Cache\User\Movie\Rating\Service $traktApiCacheUserMovieRatingService
    ) {
        $this->movieUpdateService = $movieUpdateService;
        $this->movieSelectService = $movieSelectService;
        $this->traktApi = $traktApi;
        $this->traktApiCacheUserMovieRatingService = $traktApiCacheUserMovieRatingService;
    }

    public function run() : void
    {
        $this->traktApiCacheUserMovieRatingService->set($this->traktApi->getUserMoviesRatings('leepe'));

        foreach ($this->movieSelectService->fetchAll() as $movie) {
            $rating = $this->traktApiCacheUserMovieRatingService->findRatingByTraktId($movie->getTraktId());

            $this->movieUpdateService->updateRating($movie->getId(), $rating);
        }
    }
}
