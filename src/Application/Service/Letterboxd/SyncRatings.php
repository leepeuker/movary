<?php declare(strict_types=1);

namespace Movary\Application\Service\Letterboxd;

use League\Csv\Reader;
use Movary\Api;
use Movary\Application;
use Psr\Log\LoggerInterface;

class SyncRatings
{
    private LoggerInterface $logger;

    private Application\Movie\Service\Select $movieSelectService;

    private Application\Movie\Service\Update $movieUpdateService;

    private string $ratingsCsvPath;

    private WebScrapper $webScrapper;

    public function __construct(
        Application\Movie\Service\Update $movieUpdateService,
        Application\Movie\Service\Select $movieSelectService,
        string $ratingCsvPath,
        WebScrapper $webScrapper,
        LoggerInterface $logger

    ) {
        $this->movieUpdateService = $movieUpdateService;
        $this->movieSelectService = $movieSelectService;
        $this->ratingsCsvPath = $ratingCsvPath;
        $this->webScrapper = $webScrapper;
        $this->logger = $logger;
    }

    public function execute() : void
    {
        $ratings = Reader::createFromPath($this->ratingsCsvPath);
        $ratings->setHeaderOffset(0);

        foreach ($ratings->getRecords() as $rating) {
            $movie = $this->findMovieByLetterboxdUri($rating['Letterboxd URI']);

            if ($movie === null) {
                $this->logger->error('Could not find movie with uri: ' . $rating['Letterboxd URI']);

                continue;
            }

            echo "Updating {$movie->getTitle()} with rating: " . $rating['Rating'] . PHP_EOL;

            $this->movieUpdateService->updateRating5($movie->getId(), (int)$rating['Rating']);
        }
    }

    public function findMovieByLetterboxdUri(string $letterboxdURI) : ?Application\Movie\Entity
    {
        $letterboxdId = basename($letterboxdURI);
        $movie = $this->movieSelectService->findByLetterboxdId($letterboxdId);

        if ($movie === null) {
            $tmdbId = $this->webScrapper->getProviderTmdbId($letterboxdURI);

            $movie = $this->movieSelectService->findByTmdbId($tmdbId);
            if ($movie === null) {
                return null;
            }

            $this->movieUpdateService->updateLetterboxdId($movie->getId(), $letterboxdId);
        }

        return $movie;
    }
}
