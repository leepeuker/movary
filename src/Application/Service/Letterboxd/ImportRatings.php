<?php declare(strict_types=1);

namespace Movary\Application\Service\Letterboxd;

use League\Csv\Reader;
use Movary\Api;
use Movary\Api\Letterboxd\WebScrapper;
use Movary\Application\Movie;
use Movary\Application\Service\Letterboxd\ValueObject\CsvLineRating;
use Movary\Application\SyncLog;
use Movary\ValueObject\PersonalRating;
use Psr\Log\LoggerInterface;

class ImportRatings
{
    public function __construct(
        private readonly Movie\Api $movieApi,
        private readonly WebScrapper $webScrapper,
        private readonly LoggerInterface $logger,
        private readonly SyncLog\Repository $scanLogRepository
    ) {
    }

    public function execute(int $userId, string $ratingsCsvPath, bool $verbose = false, bool $overwriteExistingData = false) : void
    {
        $ratings = Reader::createFromPath($ratingsCsvPath);
        $ratings->setHeaderOffset(0);

        foreach ($ratings->getRecords() as $rating) {
            $this->ensureValidCsvRow($rating);
            $csvLineRating = CsvLineRating::createFromCsvLine($rating);

            $movie = $this->findMovieByLetterboxdUri($csvLineRating->getLetterboxdUri());

            if ($movie === null) {
                $this->logger->info('Movie not in history: ' . $csvLineRating->getName());

                continue;
            }

            $userRating = $csvLineRating->getRating() * 2;
            $personalRating = PersonalRating::create((int)$userRating);

            if ($overwriteExistingData === false && $this->movieApi->findUserRating($movie->getId(), $userId) !== null) {
                $this->logger->info('Ignoring rating for movie: ' . $csvLineRating->getLetterboxdUri());

                $this->outputMessage("Ignoring {$movie->getTitle()} rating: " . $personalRating . PHP_EOL, $verbose);

                continue;
            }

            $this->logger->info("Updating {$movie->getTitle()} with rating: " . $personalRating);
            $this->outputMessage("Updating {$movie->getTitle()} with rating: " . $personalRating . PHP_EOL, $verbose);

            $this->movieApi->updateUserRating($movie->getId(), $userId, $personalRating);
        }

        $this->scanLogRepository->insertLogForLetterboxdSync();#

        unlink($ratingsCsvPath);
    }

    public function findMovieByLetterboxdUri(string $letterboxdUri) : ?Movie\Entity
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

    private function outputMessage(string $message, bool $verbose) : void
    {
        if ($verbose === true) {
            echo $message;
        }
    }
}
