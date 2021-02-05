<?php declare(strict_types=1);

namespace Movary\Application\Service\Tmdb;

use Movary\Api\Tmdb\Api;
use Movary\Application\Movie\Service\Select;
use Movary\Application\Movie\Service\Update;
use Movary\ValueObject\DateTime;

class SyncMovieDetails
{
    private const SYNC_VALIDITY_TIME_IN_DAYS = 7;

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
            $updatedAtTmdb = $movie->getUpdatedAtTmdb();
            if ($updatedAtTmdb !== null && $this->syncExpired($updatedAtTmdb) === false) {
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

    private function syncExpired(DateTime $updatedAtTmdb) : bool
    {
        return $updatedAtTmdb->diff(DateTime::create())->getDays() > self::SYNC_VALIDITY_TIME_IN_DAYS;
    }
}
