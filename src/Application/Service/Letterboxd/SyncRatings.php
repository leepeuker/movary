<?php declare(strict_types=1);

namespace Movary\Application\Service\Letterboxd;

use League\Csv\Reader;
use Movary\Api;
use Movary\Application;
use Psr\Log\LoggerInterface;

class SyncRatings
{
    public function __construct(
        private readonly Application\Movie\Service\Update $movieUpdateService,
        private readonly Application\Movie\Service\Select $movieSelectService,
        private readonly WebScrapper $webScrapper,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(string $ratingsCsvPath) : void
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

            echo "Updating {$movie->getTitle()} with rating: " . $rating['Rating'] . PHP_EOL;

            $this->movieUpdateService->updateRating5($movie->getId(), (int)$rating['Rating']);
        }
    }

    public function findMovieByLetterboxdUri(string $letterboxdUri) : ?Application\Movie\Entity
    {
        $letterboxdId = basename($letterboxdUri);
        $movie = $this->movieSelectService->findByLetterboxdId($letterboxdId);

        if ($movie === null) {
            $tmdbId = $this->webScrapper->getProviderTmdbId($letterboxdUri);

            $movie = $this->movieSelectService->findByTmdbId($tmdbId);
            if ($movie === null) {
                return null;
            }

            $this->movieUpdateService->updateLetterboxdId($movie->getId(), $letterboxdId);
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
