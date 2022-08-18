<?php declare(strict_types=1);

namespace Movary\Application\Service\Letterboxd;

use League\Csv\Reader;
use Movary\Api\Letterboxd\WebScrapper;
use Movary\Application\Movie;
use Movary\Application\Service\Letterboxd\ValueObject\CsvLineHistory;
use Movary\Application\Service\Tmdb;
use Movary\Application\Service\Trakt\PlaysPerDateDtoList;
use Movary\ValueObject\Job;
use Psr\Log\LoggerInterface;

class ImportHistory
{
    public function __construct(
        private readonly Movie\Api $movieApi,
        private readonly WebScrapper $webScrapper,
        private readonly LoggerInterface $logger,
        private readonly Tmdb\SyncMovie $tmdbMovieSync,
        private readonly ImportHistoryFileValidator $fileValidator,
    ) {
    }

    public function execute(int $userId, string $historyCsvPath, bool $overwriteExistingData = false) : void
    {
        $this->ensureValidCsvRow($historyCsvPath);

        $watchDates = Reader::createFromPath($historyCsvPath);
        $watchDates->setHeaderOffset(0);

        /** @var array<int, PlaysPerDateDtoList> $watchDatesToImport */
        $watchDatesToImport = [];

        foreach ($watchDates->getRecords() as $watchDate) {
            $csvLineHistory = CsvLineHistory::createFromCsvLine($watchDate);

            $movie = $this->fetchMovieByLetterboxdUri($csvLineHistory->getLetterboxdUri());

            if (empty($watchDatesToImport[$movie->getId()]) === true) {
                $watchDatesToImport[$movie->getId()] = PlaysPerDateDtoList::create();
            }

            $watchDatesToImport[$movie->getId()]->incrementPlaysForDate($csvLineHistory->getDate());
        }

        foreach ($watchDates->getRecords() as $watchDate) {
            $csvLineHistory = CsvLineHistory::createFromCsvLine($watchDate);

            $movie = $this->fetchMovieByLetterboxdUri($csvLineHistory->getLetterboxdUri());

            if ($overwriteExistingData === false && $this->movieApi->fetchHistoryCount($movie->getId()) > 0) {
                $this->logger->info('Ignoring already existing watch date for movie: ' . $movie->getTitle());

                continue;
            }

            $this->movieApi->replaceHistoryForMovieByDate(
                $movie->getId(),
                $userId,
                $csvLineHistory->getDate(),
                $watchDatesToImport[$movie->getId()]->getPlaysForDate($csvLineHistory->getDate())
            );

            $this->logger->info(sprintf('Imported watch date for movie "%s": %s', $csvLineHistory->getName(), $csvLineHistory->getDate()));
        }

        unlink($historyCsvPath);
    }

    public function executeJob(Job $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new \RuntimeException('Missing userId');
        }

        $this->execute($userId, $job->getParameters()['importFile']);
    }

    public function fetchMovieByLetterboxdUri(string $letterboxdUri) : Movie\Entity
    {
        $letterboxdId = basename($letterboxdUri);
        $movie = $this->movieApi->findByLetterboxdId($letterboxdId);

        if ($movie === null) {
            $tmdbId = $this->webScrapper->getProviderTmdbId($letterboxdUri);

            $movie = $this->movieApi->findByTmdbId($tmdbId);

            if ($movie === null) {
                $movie = $this->tmdbMovieSync->syncMovie($tmdbId);
            }

            $this->movieApi->updateLetterboxdId($movie->getId(), $letterboxdId);
        }

        return $movie;
    }

    private function ensureValidCsvRow(string $historyCsvPath) : void
    {
        if ($this->fileValidator->isValid($historyCsvPath) === false) {
            throw new \RuntimeException('Invalid letterboxed watched csv file.');
        }
    }
}