<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd;

use League\Csv\Reader;
use Movary\Api;
use Movary\Domain\Movie\MovieApi;
use Movary\JobQueue\JobEntity;
use Movary\Service\Letterboxd\Service\LetterboxdCsvValidator;
use Movary\Service\Letterboxd\Service\LetterboxdMovieFinder;
use Movary\Service\Letterboxd\ValueObject\CsvLineRating;
use Movary\Util;
use Movary\ValueObject\PersonalRating;
use Psr\Log\LoggerInterface;
use RuntimeException;

class LetterboxdImportRatings
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly LoggerInterface $logger,
        private readonly LetterboxdCsvValidator $fileValidator,
        private readonly LetterboxdMovieFinder $letterboxdMovieFinder,
        private readonly Util\File $fileUtil,
    ) {
    }

    public function execute(int $userId, string $ratingsCsvPath) : void
    {
        $this->ensureValidCsvFile($ratingsCsvPath);

        $ratingsCsv = Reader::createFromPath($ratingsCsvPath);
        $ratingsCsv->setHeaderOffset(0);

        foreach ($ratingsCsv->getRecords() as $csvLineRaw) {
            $csvLine = CsvLineRating::createFromCsvLine($csvLineRaw);

            $letterboxdId = basename($csvLine->getLetterboxdUri());

            $movie = $this->letterboxdMovieFinder->findMovieLocally($letterboxdId);
            if ($movie === null) {
                $this->logger->info('Letterboxd import: Ignoring rating because movie cannot be found locally for uri: ' . $csvLine->getLetterboxdUri());

                continue;
            }

            $personalRating = $this->convertToPersonalRating($csvLine);

            if ($this->movieApi->findUserRating($movie->getId(), $userId) !== null) {
                $this->logger->info('Letterboxd import: Ignoring rating because movie already has one: ' . $movie->getTitle());

                continue;
            }

            $this->movieApi->updateUserRating($movie->getId(), $userId, $personalRating);

            $this->logger->info("Letterboxd import: Updated {$movie->getTitle()} with rating: " . $personalRating);
        }

        $this->fileUtil->deleteFile($ratingsCsvPath);
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing userId');
        }

        $this->execute($userId, $job->getParameters()['importFile']);
    }

    private function convertToPersonalRating(CsvLineRating $csvLineRating) : ?PersonalRating
    {
        $letterboxdRating = $csvLineRating->getRating();

        if (empty($letterboxdRating) === true) {
            return null;
        }

        return PersonalRating::create($letterboxdRating * 2);
    }

    private function ensureValidCsvFile(string $ratingsCsvPath) : void
    {
        if ($this->fileValidator->isValidRatingsCsv($ratingsCsvPath) === false) {
            throw new RuntimeException('Invalid letterboxed ratings csv file.');
        }
    }
}
