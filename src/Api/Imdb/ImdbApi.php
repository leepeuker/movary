<?php declare(strict_types=1);

namespace Movary\Api\Imdb;

use Movary\Util\File;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\ImdbRating;
use Psr\Log\LoggerInterface;

class ImdbApi
{
    public function __construct(
        private readonly ImdbClient $imdbClient,
        private readonly ImdbDatasetService $imdbDatasetService,
        private readonly ImdbUrlGenerator $imdbUrlGenerator,
        private readonly LoggerInterface $logger,
        private readonly File $fileUtil,
        private readonly string $appStorageDirectory,
    ) {
    }

    public function findRating(string $imdbId) : ?ImdbRating
    {
        $ratingsFile = $this->appStorageDirectory . 'imdb_ratings.tsv';

        $this->imdbClient->downloadRatings($ratingsFile);

        $imdbRating = $this->imdbDatasetService->findRating($ratingsFile, $imdbId);

        if ($imdbRating !== null) {
            $this->logger->debug('IMDb: Found movie rating', [
                'url' => $this->imdbUrlGenerator->buildMovieUrl($imdbId),
                'average' => $imdbRating->getRating(),
                'voteCount' => $imdbRating->getVotesCount(),
            ]);
        }

        return $imdbRating;
    }

    public function getRatingsFileModificationTime() : ?DateTime
    {
        $ratingsFile = $this->appStorageDirectory . 'imdb_ratings.tsv';

        return $this->fileUtil->getModificationTime($ratingsFile);
    }
}
