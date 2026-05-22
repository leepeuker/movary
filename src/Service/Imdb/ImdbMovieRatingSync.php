<?php declare(strict_types=1);

namespace Movary\Service\Imdb;

use Movary\Api\Imdb\ImdbApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Psr\Log\LoggerInterface;

class ImdbMovieRatingSync
{
    public function __construct(
        private readonly ImdbApi $imdbApi,
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

        $ratingsFileModificationTime = $this->imdbApi->getRatingsFileModificationTime();
        $movieUpdatedAtImdb = $movie->getUpdatedAtImdb();

        if ($ratingsFileModificationTime !== null && $movieUpdatedAtImdb !== null) {
            if ($ratingsFileModificationTime->isAfter($movieUpdatedAtImdb) === false) {
                $this->logger->debug('IMDb: Skipped movie - already synced with latest ratings file', [$this->generateMovieLogData($movie)]);

                return;
            }
        }

        $this->logger->debug('IMDb: Start movie rating update', [$this->generateMovieLogData($movie)]);

        $imdbRating = $this->imdbApi->findRating($imdbId);
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
