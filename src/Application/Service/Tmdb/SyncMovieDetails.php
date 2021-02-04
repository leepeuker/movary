<?php declare(strict_types=1);

namespace Movary\Application\Service\Tmdb;

use Movary\Api\Tmdb\Api;
use Movary\Application\Movie\Service\Select;
use Movary\Application\Movie\Service\Update;

class SyncMovieDetails
{
    private Api $api;

    private Select $movieSelectService;

    private Update $movieUpdateService;

    public function __construct(Api $api, Select $movieSelectService, Update $movieUpdateService)
    {
        $this->api = $api;
        $this->movieSelectService = $movieSelectService;
        $this->movieUpdateService = $movieUpdateService;
    }

    public function execute() : void
    {
        $movies = $this->movieSelectService->fetchAll();

        foreach ($movies as $movie) {
            if ($movie->getUpdatedAtTmdb() !== null) {
                continue;
            }

            $movieDetails = $this->api->getMovieDetails($movie->getTmdbId());

            $this->movieUpdateService->updateDetails(
                $movie->getId(),
                $movieDetails->getOverview(),
                $movieDetails->getOriginalLanguage(),
                $movieDetails->getReleaseDate(),
                $movieDetails->getRuntime(),
                $movieDetails->getVoteAverage(),
                $movieDetails->getVoteCount(),
            );
        }
    }
}
