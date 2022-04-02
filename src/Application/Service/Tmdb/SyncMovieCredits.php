<?php declare(strict_types=1);

namespace Movary\Application\Service\Tmdb;

use Movary\Api;
use Movary\Application\Movie;

class SyncMovieCredits
{
    private Movie\Service\Select $movieSelectService;

    private Api\Tmdb\Api $tmdbApi;

    public function __construct(
        Movie\Service\Select $movieSelectService,
        Api\Tmdb\Api $traktApi
    ) {
        $this->movieSelectService = $movieSelectService;
        $this->tmdbApi = $traktApi;
    }

    public function execute() : void
    {
        foreach ($this->movieSelectService->fetchAll() as $movie) {
            $credits = $this->tmdbApi->getMovieCredits($movie->getTmdbId());

            foreach ($credits->getCast() as $castMember) {
                // var_dump($castMember);
                break;
            }

            foreach ($credits->getCrew() as $crewMember) {
                // var_dump($crewMember);
                exit;
            }

            break;
        }
    }
}
