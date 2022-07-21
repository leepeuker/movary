<?php declare(strict_types=1);

namespace Movary\Application\Service\Letterboxd;

use Movary\Api;

class ImportRatings
{
    public function __construct(
        // private readonly Application\Movie\Api $movieApi,
        // private readonly WebScrapper $webScrapper,
        // private readonly LoggerInterface $logger,
        // private readonly Application\SyncLog\Repository $scanLogRepository
    )
    {
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function execute(int $userId, string $ratingsCsvPath, bool $verbose = false, bool $overwriteExistingData = false) : void
    {
        // TODO fix this stuff
        // $ratings = Reader::createFromPath($ratingsCsvPath);
        // $ratings->setHeaderOffset(0);
        //
        // foreach ($ratings->getRecords() as $rating) {
        //     $this->ensureValidCsvRow($rating);
        //
        //     $movie = $this->findMovieByLetterboxdUri($rating['Letterboxd URI']);
        //
        //     if ($movie === null) {
        //         $this->logger->error('Could not find movie with uri: ' . $rating['Letterboxd URI']);
        //
        //         continue;
        //     }
        //
        //     $ratingWithScale10 = $rating['Rating'] * 2;
        //     $personalRating = PersonalRating::create((int)$ratingWithScale10);
        //
        //     if ($overwriteExistingData === false && $movie->getUserRating() !== null) {
        //         $this->outputMessage("Ignoring {$movie->getTitle()} rating: " . $personalRating . PHP_EOL, $verbose);
        //
        //         continue;
        //     }
        //
        //     $this->outputMessage("Updating {$movie->getTitle()} with rating: " . $personalRating . PHP_EOL, $verbose);
        //
        //     $this->movieApi->updateUserRating($movie->getId(), $personalRating);
        // }
        //
        // $this->scanLogRepository->insertLogForLetterboxdSync();#

        unlink($ratingsCsvPath);
    }

    // public function findMovieByLetterboxdUri(string $letterboxdUri) : ?Application\Movie\Entity
    // {
    //     $letterboxdId = basename($letterboxdUri);
    //     $movie = $this->movieApi->findByLetterboxdId($letterboxdId);
    //
    //     if ($movie === null) {
    //         $tmdbId = $this->webScrapper->getProviderTmdbId($letterboxdUri);
    //
    //         $movie = $this->movieApi->findByTmdbId($tmdbId);
    //         if ($movie === null) {
    //             return null;
    //         }
    //
    //         $this->movieApi->updateLetterboxdId($movie->getId(), $letterboxdId);
    //     }
    //
    //     return $movie;
    // }
    //
    // private function ensureValidCsvRow(array $rating) : void
    // {
    //     if (empty($rating['Letterboxd URI']) === true || empty($rating['Rating']) === true) {
    //         throw new \RuntimeException('Invalid csv row in letterboxed rating csv.');
    //     }
    // }
    //
    // private function outputMessage(string $message, bool $verbose) : void
    // {
    //     if ($verbose === true) {
    //         echo $message;
    //     }
    // }
}
