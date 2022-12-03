<?php declare(strict_types=1);

namespace Movary\Application\Service\Imdb;

use Movary\Api\Imdb\WebScrapper;
use Movary\Application\Movie;
use Psr\Log\LoggerInterface;

class SyncMovies
{
    public function __construct(
        private readonly WebScrapper $imdbWebScrapper,
        private readonly Movie\MovieApi $movieApi,
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
            } catch (\Throwable $t) {
                $this->logger->error('Could not sync imdb rating for movie with id "' . $movie->getId() . '". Error: ' . $t->getMessage(), ['exception' => $t]);
            }

            // Hacky way to prevent imdb rate limits
            usleep(500000);
        }
    }
}
