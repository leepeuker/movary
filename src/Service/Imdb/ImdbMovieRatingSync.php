<?php declare(strict_types=1);

namespace Movary\Service\Imdb;

use Exception;
use Movary\Api\Imdb\ImdbWebScrapper;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\ValueObject\ImdbRating;
use Psr\Log\LoggerInterface;

class ImdbMovieRatingSync
{
    private const int DEFAULT_MIN_DELAY_BETWEEN_REQUESTS_IN_MS = 1000000;

    private const int SLEEP_AFTER_FIRST_FAILED_REQUEST_IN_MS = 2000000;

    public function __construct(
        private readonly ImdbWebScrapper $imdbWebScrapper,
        private readonly MovieApi $movieApi,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function syncMovieRating(int $movieId) : void
    {
        $movie = $this->movieApi->findById($movieId);
        $imdbId = $movie?->getImdbId();

        if ($movie === null || $imdbId === null) {
            return;
        }

        $this->logger->debug('IMDb: Start movie rating update', [$this->generateMovieLogData($movie)]);

        $imdbRating = $this->findRating($imdbId);
        if ($imdbRating === null) {
            $this->movieApi->updateImdbTimestamp($movieId);

            return;
        }

        if ($imdbRating->getRating() === $movie->getImdbRatingAverage() && $imdbRating->getVotesCount() === $movie->getImdbVoteCount()) {
            $this->logger->debug('IMDb: Skipped updating not changed movie rating', [$this->generateMovieLogData($movie)]);

            $this->movieApi->updateImdbTimestamp($movieId);

            return;
        }

        $this->movieApi->updateImdbRating($movieId, $imdbRating);

        $this->logger->info('IMDb: Updated movie rating', [
            array_merge(
                $this->generateMovieLogData($movie),
                [
                    'oldAverage' => $movie->getImdbRatingAverage(),
                    'oldVoteCount' => $movie->getImdbVoteCount(),
                    'newAverage' => $imdbRating->getRating(),
                    'newVoteCount' => $imdbRating->getVotesCount(),
                ],
            )
        ]);
    }

    public function syncMultipleMovieRatings(
        ?int $maxAgeInHours = null,
        ?int $movieCountSyncThreshold = null,
        ?array $movieIds = null,
        ?bool $onlyNeverSynced = false,
    ) : void {
        $movieIds = $this->movieApi->fetchMovieIdsHavingImdbIdOrderedByLastImdbUpdatedAt($maxAgeInHours, $movieCountSyncThreshold, $movieIds, (bool)$onlyNeverSynced);

        foreach ($movieIds as $index => $movieId) {
            $this->syncMovieRating($movieId);

            if ($index === array_key_last($movieIds) || ((int)$movieCountSyncThreshold !== 0 && (int)$index + 1 >= $movieCountSyncThreshold)) {
                break;
            }

            // Hacky way to prevent imdb rate limits
            usleep(self::DEFAULT_MIN_DELAY_BETWEEN_REQUESTS_IN_MS);
        }
    }

    private function findRating(string $imdbId) : ?ImdbRating
    {
        try {
            return $this->imdbWebScrapper->findRating($imdbId);
        } catch (Exception) {
            // Retry request with a little delay to circumvent onetime network issues
            usleep(self::SLEEP_AFTER_FIRST_FAILED_REQUEST_IN_MS);

            try {
                return $this->imdbWebScrapper->findRating($imdbId);
            } catch (Exception) {
                return null;
            }
        }
    }

    private function generateMovieLogData(MovieEntity $movie) : array
    {
        return [
            'movieId' => $movie->getId(),
            'imdbId' => $movie->getImdbId(),
            'movieTitle' => $movie->getTitle(),
        ];
    }
}
