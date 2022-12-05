<?php declare(strict_types=1);

namespace Movary\Service\Imdb;

use Movary\Api\Imdb\ImdbWebScrapper;
use Movary\Domain\Movie\MovieApi;
use Psr\Log\LoggerInterface;
use Throwable;

class SyncMovies
{
    public function __construct(
        private readonly ImdbWebScrapper $imdbWebScrapper,
        private readonly MovieApi $movieApi,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function syncMovies(?int $maxAgeInHours = null, ?int $movieCountSyncThreshold = null) : void
    {
        $movies = $this->movieApi->fetchAllOrderedByLastUpdatedAtImdbAsc($maxAgeInHours, $movieCountSyncThreshold);

        foreach ($movies as $movie) {
            $imdbId = $movie->getImdbId();

            if ($imdbId === null) {
                continue;
            }

            try {
                $imdbRating = $this->imdbWebScrapper->findRating($imdbId);

                $this->movieApi->updateImdbRating($movie->getId(), $imdbRating['average'], $imdbRating['voteCount']);
            } catch (Throwable $t) {
                $this->logger->error('Could not sync imdb rating for movie with id "' . $movie->getId() . '". Error: ' . $t->getMessage(), ['exception' => $t]);
            }

            // Hacky way to prevent imdb rate limits
            usleep(500000);
        }
    }
}
