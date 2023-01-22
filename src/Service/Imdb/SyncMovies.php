<?php declare(strict_types=1);

namespace Movary\Service\Imdb;

use Movary\Domain\Movie\MovieApi;

class SyncMovies
{
    private const DEFAULT_MIN_DELAY_BETWEEN_REQUESTS_IN_MS = 2000000;

    public function __construct(
        private readonly SyncMovie $syncMovie,
        private readonly MovieApi $movieApi,
    ) {
    }

    public function syncMovies(
        ?int $maxAgeInHours = null,
        ?int $movieCountSyncThreshold = null,
        int $minDelayBetweenRequests = self::DEFAULT_MIN_DELAY_BETWEEN_REQUESTS_IN_MS,
    ) : void {
        $movies = $this->movieApi->fetchAllOrderedByLastUpdatedAtImdbAsc($maxAgeInHours, $movieCountSyncThreshold);

        foreach ($movies as $movie) {
            $imdbId = $movie->getImdbId();

            if ($imdbId === null) {
                continue;
            }

            $this->syncMovie->syncMovie($movie->getId(), $imdbId, $movie->getTitle());

            // Hacky way to prevent imdb rate limits
            /** @psalm-suppress ArgumentTypeCoercion */
            usleep($minDelayBetweenRequests);
        }
    }
}
