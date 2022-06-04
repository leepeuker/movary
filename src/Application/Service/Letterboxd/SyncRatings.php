<?php declare(strict_types=1);

namespace Movary\Application\Service\Letterboxd;

use League\Csv\Reader;
use Movary\Api;
use Movary\Api\Letterboxd\WebScrapper;
use Movary\Application;
use Psr\Log\LoggerInterface;

class SyncRatings
{
    public function __construct(
        private readonly Application\Movie\Api $movieApi,
        private readonly WebScrapper $webScrapper,
        private readonly LoggerInterface $logger,
        private readonly Application\SyncLog\Repository $scanLogRepository
    ) {
    }

    public function execute(string $ratingsCsvPath, bool $verbose = false) : void
    {
        $ratings = Reader::createFromPath($ratingsCsvPath);
        $ratings->setHeaderOffset(0);

        foreach ($ratings->getRecords() as $rating) {
            $this->ensureValidCsvRow($rating);

            $movie = $this->findMovieByLetterboxdUri($rating['Letterboxd URI']);

            if ($movie === null) {
                $this->logger->error('Could not find movie with uri: ' . $rating['Letterboxd URI']);

                continue;
            }

            if ($verbose === true) {
                echo "Updating {$movie->getTitle()} with rating: " . $rating['Rating'] . PHP_EOL;
            }

            $this->movieApi->updateRating5($movie->getId(), (int)$rating['Rating']);
        }

        $this->scanLogRepository->insertLogForLetterboxdSync();
    }

    public function findMovieByLetterboxdUri(string $letterboxdUri) : ?Application\Movie\Entity
    {
        $letterboxdId = basename($letterboxdUri);
        $movie = $this->movieApi->findByLetterboxdId($letterboxdId);

        if ($movie === null) {
            $tmdbId = $this->webScrapper->getProviderTmdbId($letterboxdUri);

            $movie = $this->movieApi->findByTmdbId($tmdbId);
            if ($movie === null) {
                return null;
            }

            $this->movieApi->updateLetterboxdId($movie->getId(), $letterboxdId);
        }

        return $movie;
    }

    private function ensureValidCsvRow(array $rating) : void
    {
        if (empty($rating['Letterboxd URI']) === true || empty($rating['Rating']) === true) {
            throw new \RuntimeException('Invalid csv row in letterboxed rating csv.');
        }
    }
}
