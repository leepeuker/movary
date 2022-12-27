<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd;

use League\Csv\Reader;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\JobQueue\JobEntity;
use Movary\Service\Letterboxd\ValueObject\CsvLineHistory;
use Movary\Service\Trakt\PlaysPerDateDtoList;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ImportHistory
{
    private array $blacklistedLetterboxedUris = [];

    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly LoggerInterface $logger,
        private readonly ImportHistoryFileValidator $fileValidator,
        private readonly LetterboxdMovieImporter $letterboxdMovieImporter,
    ) {
    }

    public function execute(int $userId, string $historyCsvPath) : void
    {
        $this->ensureValidCsvRow($historyCsvPath);

        $watchDatesCsv = Reader::createFromPath($historyCsvPath);
        $watchDatesCsv->setHeaderOffset(0);
        $watchDateRecords = $watchDatesCsv->getRecords();

        /** @var array<int, PlaysPerDateDtoList> $watchDatesToImport */
        $watchDatesToImport = [];

        foreach ($watchDateRecords as $watchDateRecord) {
            $csvLineHistory = CsvLineHistory::createFromCsvLine($watchDateRecord);
            $letterboxdUri = $csvLineHistory->getLetterboxdUri();

            $movie = $this->getMovieFromCsvLineRecord($letterboxdUri);

            if ($movie === null) {
                continue;
            }

            if (empty($watchDatesToImport[$movie->getId()]) === true) {
                $watchDatesToImport[$movie->getId()] = PlaysPerDateDtoList::create();
            }

            $watchDatesToImport[$movie->getId()]->incrementPlaysForDate($csvLineHistory->getDate());
        }

        foreach ($watchDateRecords as $watchDateRecord) {
            $csvLineHistory = CsvLineHistory::createFromCsvLine($watchDateRecord);
            $letterboxdUri = $csvLineHistory->getLetterboxdUri();
            $watchDate = $csvLineHistory->getDate();

            if (isset($this->blacklistedLetterboxedUris[$letterboxdUri]) === true) {
                continue;
            }

            $movie = $this->getMovieFromCsvLineRecord($letterboxdUri);
            if ($movie === null) {
                continue;
            }

            if ($this->movieApi->fetchHistoryMoviePlaysOnDate($movie->getId(), $userId, $watchDate) > 0) {
                $this->logger->info('Letterboxd import: Ignoring movie watch date because it was already set: ' . $movie->getTitle());

                continue;
            }

            $this->movieApi->replaceHistoryForMovieByDate(
                $movie->getId(),
                $userId,
                $watchDate,
                $watchDatesToImport[$movie->getId()]->getPlaysForDate($watchDate),
            );

            $this->logger->info(sprintf('Letterboxd import: Imported watch date for movie "%s": %s', $csvLineHistory->getName(), $watchDate));
        }

        unlink($historyCsvPath);
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing userId');
        }

        $this->execute($userId, $job->getParameters()['importFile']);
    }

    private function ensureValidCsvRow(string $historyCsvPath) : void
    {
        if ($this->fileValidator->isValid($historyCsvPath) === false) {
            throw new RuntimeException('Invalid letterboxed watched csv file.');
        }
    }

    private function getMovieFromCsvLineRecord(string $letterboxdUri) : ?MovieEntity
    {
        try {
            return $this->letterboxdMovieImporter->importMovieByLetterboxdUri($letterboxdUri);
        } catch (\Exception $e) {
            $this->logger->warning('Letterboxd import: Could not import movie by uri: ' . $letterboxdUri, ['exception' => $e]);

            $this->blacklistedLetterboxedUris[$letterboxdUri] = true;
        }

        return null;
    }
}
