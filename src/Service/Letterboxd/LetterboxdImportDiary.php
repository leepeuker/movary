<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd;

use Exception;
use Iterator;
use League\Csv\Reader;
use Movary\Domain\Movie\MovieApi;
use Movary\JobQueue\JobEntity;
use Movary\Service\Letterboxd\Service\LetterboxdCsvValidator;
use Movary\Service\Letterboxd\Service\LetterboxdMovieImporter;
use Movary\Service\Letterboxd\ValueObject\CsvLineDiary;
use Movary\Service\Trakt\WatchDateToPlaysMap;
use Movary\Util;
use Movary\ValueObject\Date;
use Psr\Log\LoggerInterface;
use RuntimeException;

class LetterboxdImportDiary
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly LoggerInterface $logger,
        private readonly LetterboxdCsvValidator $fileValidator,
        private readonly LetterboxdMovieImporter $letterboxdMovieImporter,
        private readonly Util\File $fileUtil,
    ) {
    }

    public function execute(int $userId, string $diaryCsvPath) : void
    {
        if ($this->fileValidator->isValidDiaryCsv($diaryCsvPath) === false) {
            throw new RuntimeException('Invalid letterboxed diary csv file.');
        }

        $diaryCsv = Reader::createFromPath($diaryCsvPath);
        $diaryCsv->setHeaderOffset(0);
        $diaryRecords = $diaryCsv->getRecords();

        $aggregatedWatchDates = $this->aggregateWatchDatesAndImportMissingMovies($diaryRecords);

        $this->updateWatchDates($userId, $aggregatedWatchDates);

        $this->fileUtil->deleteFile($diaryCsvPath);
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing userId');
        }

        $this->execute($userId, $job->getParameters()['importFile']);
    }

    private function aggregateWatchDatesAndImportMissingMovies(Iterator $watchDateRecords) : array
    {
        /** @var array<int, WatchDateToPlaysMap> $watchDatesToImport */
        $watchDatesToImport = [];

        foreach ($watchDateRecords as $watchDateRecord) {
            $csvLineHistory = CsvLineDiary::createFromCsvLine($watchDateRecord);

            try {
                $movieId = $this->letterboxdMovieImporter->importMovieByDiaryUri($csvLineHistory->getLetterboxdDiaryEntryUri())->getId();
            } catch (Exception $e) {
                $this->logger->warning('Letterboxd import: Could not import movie: ' . $csvLineHistory->getName(), ['exception' => $e]);

                continue;
            }

            if (empty($watchDatesToImport[$movieId]) === true) {
                $watchDatesToImport[$movieId] = WatchDateToPlaysMap::create();
            }

            $watchDatesToImport[$movieId]->incrementPlaysForDate($csvLineHistory->getWatchedDate());
        }

        return $watchDatesToImport;
    }

    private function updateWatchDates(int $userId, array $movieToWatchDatesToPlaysMap) : void
    {
        foreach ($movieToWatchDatesToPlaysMap as $movieId => $watchDateToPlaysMap) {
            $movie = $this->movieApi->fetchById($movieId);

            foreach ($watchDateToPlaysMap as $watchDate => $plays) {
                $watchDate = Date::createFromString($watchDate);

                if ($this->movieApi->findHistoryEntryForMovieByUserOnDate($movieId, $userId, $watchDate) !== null) {
                    $this->logger->info('Letterboxd import: Ignoring movie watch date because it was already set for movie: ' . $movie->getTitle());

                    continue;
                }

                $this->movieApi->replaceHistoryForMovieByDate($movieId, $userId, $watchDate, $plays);

                $this->logger->info(sprintf('Letterboxd import: Imported watch date for movie "%s": %s', $movie->getTitle(), $watchDate));
            }
        }
    }
}
