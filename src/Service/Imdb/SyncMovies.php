<?php declare(strict_types=1);

namespace Movary\Service\Imdb;

use Movary\Api\Imdb\ImdbWebScrapper;
use Movary\Domain\Movie\MovieApi;
use Psr\Log\LoggerInterface;
use Throwable;

class SyncMovies
{
    private const SLEEP_AFTER_FIRST_FAILED_REQUEST_IN_MS = 4000000;

    private const DEFAULT_MIN_DELAY_BETWEEN_REQUESTS_IN_MS = 2000000;

    public function __construct(
        private readonly ImdbWebScrapper $imdbWebScrapper,
        private readonly MovieApi $movieApi,
        private readonly LoggerInterface $logger,
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

            try {
                $imdbRating = $this->imdbWebScrapper->findRating($imdbId);
            } catch (Throwable) {
                /** @psalm-suppress ArgumentTypeCoercion */
                usleep(self::SLEEP_AFTER_FIRST_FAILED_REQUEST_IN_MS);

                try {
                    $imdbRating = $this->imdbWebScrapper->wfindRating($imdbId);
                } catch (Throwable $t) {
                    $this->logger->warning('Could not sync imdb rating for movie with id "' . $movie->getId() . '". Error: ' . $t->getMessage(), ['exception' => $t]);

                    continue;
                }
            }
            $this->movieApi->updateImdbRating($movie->getId(), $imdbRating['average'], $imdbRating['voteCount']);

            $this->logger->debug('Imdb sync: Updated imdb rating for movie', [
                'movieTitle' => $movie->getTitle(),
                'average' => $imdbRating['average'],
                'voteCount' => $imdbRating['voteCount']
            ]);

            // Hacky way to prevent imdb rate limits
            /** @psalm-suppress ArgumentTypeCoercion */
            usleep($minDelayBetweenRequests);
        }
    }
}
