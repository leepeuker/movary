<?php declare(strict_types=1);

namespace Movary\Application\Service\Tmdb;

use Movary\Api;
use Movary\Application\Movie;
use Movary\ValueObject\DateTime;

class SyncMovieCredits
{
    private const SYNC_VALIDITY_TIME_IN_DAYS = 7;

    private Movie\Service\Select $movieSelectService;

    private Api\Tmdb\Api $tmdbApi;

    private Movie\Service\Update $updateMovieService;

    public function __construct(
        Movie\Service\Select $movieSelectService,
        Api\Tmdb\Api $traktApi,
        Movie\Service\Update $updateMovieService
    ) {
        $this->movieSelectService = $movieSelectService;
        $this->tmdbApi = $traktApi;
        $this->updateMovieService = $updateMovieService;
    }

    public function execute() : void
    {
        // TODO is always resyncing for all movies. fix this

        foreach ($this->movieSelectService->fetchAll() as $movie) {
            $updatedAtTmdb = $movie->getUpdatedAtTmdb();
            if ($updatedAtTmdb !== null && $this->syncExpired($updatedAtTmdb) === false) {
                continue;
            }

            $credits = $this->tmdbApi->getMovieCredits($movie->getTmdbId());

            $this->updateMovieService->updateCast($movie->getId(), $credits->getCast());
            $this->updateMovieService->updateCrew($movie->getId(), $credits->getCrew());
        }
    }

    private function syncExpired(DateTime $updatedAtTmdb) : bool
    {
        return $updatedAtTmdb->diff(DateTime::create())->getDays() > self::SYNC_VALIDITY_TIME_IN_DAYS;
    }
}
