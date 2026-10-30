<?php declare(strict_types=1);

namespace Movary\Service\Export;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Review\MovieReviewApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use RuntimeException;

class ExportService
{
    private const string CSV_HEADER_HISTORY = 'title,year,tmdbId,imdbId,watchedAt,comment,location' . PHP_EOL;

    private const string CSV_HEADER_RATINGS = 'title,year,tmdbId,imdbId,userRating' . PHP_EOL;

    private const string CSV_HEADER_REVIEWS = 'title,year,tmdbId,imdbId,review,containsSpoilers,createdAt,updatedAt' . PHP_EOL;

    private const string CSV_HEADER_WATCHLIST = 'title,year,tmdbId,imdbId,addedAt' . PHP_EOL;

    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly MovieWatchlistApi $watchlistApi,
        private readonly MovieReviewApi $reviewApi,
        private readonly ExportWriter $dataMapper,
        private readonly string $storageDirectory,
    ) {
    }

    public function createExportHistoryCsv(int $userId, ?string $fileName = null) : string
    {
        $plays = $this->movieApi->fetchPlaysForExport($userId);

        if ($fileName === null) {
            $fileName = $this->generateFilename($userId, 'history');
        }

        $exportFileHandle = $this->createFileHandle($fileName);

        fwrite($exportFileHandle, self::CSV_HEADER_HISTORY);

        foreach ($plays as $play) {
            $this->dataMapper->writePlayToCsv($exportFileHandle, $play);
        }

        fclose($exportFileHandle);

        return $fileName;
    }

    public function createExportRatingsCsv(int $userId, ?string $fileName = null) : string
    {
        $movies = $this->movieApi->fetchAll();

        if ($fileName === null) {
            $fileName = $this->generateFilename($userId, 'ratings');
        }

        $exportFileHandle = $this->createFileHandle($fileName);

        fwrite($exportFileHandle, self::CSV_HEADER_RATINGS);

        foreach ($movies as $movie) {
            $this->dataMapper->writeUserRatingToCsv($exportFileHandle, $movie, $userId);
        }

        fclose($exportFileHandle);

        return $fileName;
    }

    public function createExportReviewsCsv(int $userId, ?string $fileName = null) : string
    {
        if ($fileName === null) {
            $fileName = $this->generateFilename($userId, 'reviews');
        }

        $exportFileHandle = $this->createFileHandle($fileName);
        fwrite($exportFileHandle, self::CSV_HEADER_REVIEWS);

        foreach ($this->reviewApi->fetchAllForUser($userId) as $review) {
            $this->dataMapper->writeReviewToCsv($exportFileHandle, $review);
        }

        fclose($exportFileHandle);

        return $fileName;
    }

    public function createExportWatchlistCsv(int $userId, ?string $fileName = null) : ?string
    {
        $watchlist = $this->watchlistApi->fetchAllWatchlistItems($userId);

        if ($fileName === null) {
            $fileName = $this->generateFilename($userId, 'watchlist');
        }

        $exportFileHandle = $this->createFileHandle($fileName);

        fwrite($exportFileHandle, self::CSV_HEADER_WATCHLIST);

        foreach ($watchlist as $watchlistItem) {
            $this->dataMapper->writeWatchlistItemToCsv($exportFileHandle, $watchlistItem);
        }

        fclose($exportFileHandle);

        return $fileName;
    }

    /** @return resource */
    private function createFileHandle(string $fileName)
    {
        $exportFileHandle = fopen($fileName, 'wb');

        if ($exportFileHandle === false) {
            throw new RuntimeException('Could not create or open export file: ' . $fileName);
        }

        return $exportFileHandle;
    }

    private function generateFilename(int $userId, string $exportType) : string
    {
        $timestamp = time();

        return $this->storageDirectory . "export-{$exportType}-{$userId}-{$timestamp}.csv";
    }
}
