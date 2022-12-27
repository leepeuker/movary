<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd;

use League\Csv\Reader;
use Movary\Api;
use Movary\Api\Letterboxd\LetterboxdWebScrapper;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\JobQueue\JobEntity;
use Movary\Service\Letterboxd\ValueObject\CsvLineRating;
use Movary\ValueObject\PersonalRating;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ImportRatings
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly LoggerInterface $logger,
        private readonly ImportRatingsFileValidator $fileValidator,
        private readonly LetterboxdWebScrapper $webScrapper,
    ) {
    }

    public function execute(int $userId, string $ratingsCsvPath) : void
    {
        $this->ensureValidCsvFile($ratingsCsvPath);

        $ratings = Reader::createFromPath($ratingsCsvPath);
        $ratings->setHeaderOffset(0);

        foreach ($ratings->getRecords() as $rating) {
            $csvLineRating = CsvLineRating::createFromCsvLine($rating);
            $letterboxdUri = $csvLineRating->getLetterboxdUri();

            $movie = $this->findMovie($letterboxdUri);
            if ($movie === null) {
                $this->logger->info('Letterboxd import: Ignoring rating because movie cannot be found locally for uri: ' . $csvLineRating->getLetterboxdUri());

                continue;
            }

            $userRating = $csvLineRating->getRating() * 2;
            $personalRating = PersonalRating::create((int)$userRating);

            if ($this->movieApi->findUserRating($movie->getId(), $userId) !== null) {
                $this->logger->info('Letterboxd import: Ignoring rating because movie already has one: ' . $movie->getTitle());

                continue;
            }

            $this->logger->info("Letterboxd import: Updating {$movie->getTitle()} with rating: " . $personalRating);

            $this->movieApi->updateUserRating($movie->getId(), $userId, $personalRating);
        }

        unlink($ratingsCsvPath);
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing userId');
        }

        $this->execute($userId, $job->getParameters()['importFile']);
    }

    public function findMovie(string $letterboxdUri) : ?MovieEntity
    {
        $letterboxdId = basename($letterboxdUri);

        $movie = $this->movieApi->findByLetterboxdId($letterboxdId);

        if ($movie !== null) {
            return $movie;
        }

        try {
            $tmdbId = $this->webScrapper->getProviderTmdbId($letterboxdUri);

            $movie = $this->movieApi->findByTmdbId($tmdbId);
        } catch (\Exception $e) {
            $movie = null;
        }

        if ($movie === null) {
            return null;
        }

        $this->movieApi->updateLetterboxdId($movie->getId(), $letterboxdId);

        return $movie;
    }

    private function ensureValidCsvFile(string $ratingsCsvPath) : void
    {
        if ($this->fileValidator->isValid($ratingsCsvPath) === false) {
            throw new RuntimeException('Invalid letterboxed ratings csv file.');
        }
    }
}
