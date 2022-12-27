<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd;

use League\Csv\Reader;
use Movary\Api;
use Movary\Domain\Movie\MovieApi;
use Movary\JobQueue\JobEntity;
use Movary\Service\Letterboxd\ValueObject\CsvLineRating;
use Movary\ValueObject\PersonalRating;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ImportRatings
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly LetterboxdMovieImporter $letterboxdMovieImporter,
        private readonly LoggerInterface $logger,
        private readonly ImportRatingsFileValidator $fileValidator,
    ) {
    }

    public function execute(int $userId, string $ratingsCsvPath, bool $verbose = false, bool $overwriteExistingData = false) : void
    {
        $this->ensureValidCsvFile($ratingsCsvPath);

        $ratings = Reader::createFromPath($ratingsCsvPath);
        $ratings->setHeaderOffset(0);

        foreach ($ratings->getRecords() as $rating) {
            $csvLineRating = CsvLineRating::createFromCsvLine($rating);

            try {
                $movie = $this->letterboxdMovieImporter->importMovieByLetterboxdUri($csvLineRating->getLetterboxdUri());
            } catch (\Exception $e) {
                $this->logger->warning('Letterboxd import: Could not import movie by uri: ' . $csvLineRating->getLetterboxdUri(), ['exception' => $e]);

                continue;
            }

            $userRating = $csvLineRating->getRating() * 2;
            $personalRating = PersonalRating::create((int)$userRating);

            if ($overwriteExistingData === false && $this->movieApi->findUserRating($movie->getId(), $userId) !== null) {
                $this->logger->info('Letterboxd import: Ignoring rating for movie, rating already set: ' . $movie->getTitle());

                continue;
            }

            $this->logger->info("Updating {$movie->getTitle()} with rating: " . $personalRating);

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

    private function ensureValidCsvFile(string $ratingsCsvPath) : void
    {
        if ($this->fileValidator->isValid($ratingsCsvPath) === false) {
            throw new RuntimeException('Invalid letterboxed ratings csv file.');
        }
    }
}
