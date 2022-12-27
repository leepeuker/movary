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
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly LoggerInterface $logger,
        private readonly ImportHistoryFileValidator $fileValidator,
        private readonly LetterboxdMovieImporter $letterboxdMovieImporter,
    ) {
    }

    public function execute(int $userId, string $historyCsvPath, bool $overwriteExistingData = false) : void
    {
        $this->ensureValidCsvRow($historyCsvPath);

        $watchDatesCsv = Reader::createFromPath($historyCsvPath);
        $watchDatesCsv->setHeaderOffset(0);
        $watchDateRecords = $watchDatesCsv->getRecords();

        /** @var array<int, PlaysPerDateDtoList> $watchDatesToImport */
        $watchDatesToImport = [];

        foreach ($watchDateRecords as $watchDateRecord) {
            $csvLineHistory = CsvLineHistory::createFromCsvLine($watchDateRecord);

            $movie = $this->getMovieFromWatchDateRecord($csvLineHistory);

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

            $movie = $this->getMovieFromWatchDateRecord($csvLineHistory);

            if ($movie === null) {
                continue;
            }

            if ($overwriteExistingData === false && $this->movieApi->fetchHistoryCount($movie->getId()) > 0) {
                $this->logger->info('Letterboxd import: Ignoring already existing watch date for movie: ' . $movie->getTitle());

                continue;
            }

            $this->movieApi->replaceHistoryForMovieByDate(
                $movie->getId(),
                $userId,
                $csvLineHistory->getDate(),
                $watchDatesToImport[$movie->getId()]->getPlaysForDate($csvLineHistory->getDate()),
            );

            $this->logger->info(sprintf('Letterboxd import: Imported watch date for movie "%s": %s', $csvLineHistory->getName(), $csvLineHistory->getDate()));
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

    private function getMovieFromWatchDateRecord(CsvLineHistory $csvLineHistory) : ?MovieEntity
    {
        $letterboxdUri = $csvLineHistory->getLetterboxdUri();

        try {
            return $this->letterboxdMovieImporter->importMovieByLetterboxdUri($letterboxdUri);
        } catch (\Exception $e) {
            $this->logger->warning('Letterboxd import: Could not import movie by uri: ' . $letterboxdUri, ['exception' => $e]);
        }

        return null;
    }
}
