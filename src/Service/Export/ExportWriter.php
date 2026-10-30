<?php declare(strict_types=1);

namespace Movary\Service\Export;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Year;
use RuntimeException;

class ExportWriter
{
    public function __construct(private readonly MovieApi $movieApi)
    {
    }

    /** @param resource $fileHandle */
    public function writeUserRatingToCsv($fileHandle, MovieEntity $movie, int $userId) : void
    {
        $userRating = $this->movieApi->findUserRating($movie->getId(), $userId);

        if ($userRating === null) {
            return;
        }

        $lengthOfWrittenString = fputcsv($fileHandle, [
            $movie->getTitle(),
            (string)$movie->getReleaseDate()?->format('Y'),
            $movie->getTmdbId(),
            (string)$movie->getImdbId(),
            (string)$userRating,
        ]);

        if ($lengthOfWrittenString === false) {
            throw new RuntimeException('Could not write watch date to export csv');
        }
    }

    /** @param resource $fileHandle */
    public function writeReviewToCsv($fileHandle, array $review) : void
    {
        $lengthOfWrittenString = fputcsv(
            $fileHandle,
            [
                $review['title'],
                $this->convertReleaseDate($review['release_date']),
                $review['tmdb_id'],
                $review['imdb_id'],
                $review['review'],
                (int)$review['contains_spoilers'],
                $review['created_at'],
                $review['updated_at'],
            ],
            ',',
            '"',
            '',
        );

        if ($lengthOfWrittenString === false) {
            throw new RuntimeException('Could not write review to export csv');
        }
    }

    /** @param resource $fileHandle */
    public function writePlayToCsv($fileHandle, array $play) : void
    {
        $releaseDate = $this->convertReleaseDate($play['release_date']);
        $watchDate = empty($play['watched_at']) === false ? Date::createFromString($play['watched_at']) : null;

        $lengthOfWrittenString = fputcsv($fileHandle, [
            $play['title'],
            $releaseDate,
            $play['tmdb_id'],
            $play['imdb_id'],
            $watchDate,
            $play['comment'],
            $play['location_name'],
        ], ',', '"', '\\');

        if ($lengthOfWrittenString === false) {
            throw new RuntimeException('Could not write play to export csv');
        }
    }

    /** @param resource $fileHandle */
    public function writeWatchlistItemToCsv($fileHandle, array $watchlistItem) : void
    {
        $lengthOfWrittenString = fputcsv($fileHandle, [
            $watchlistItem['title'],
            $this->convertReleaseDate($watchlistItem['release_date']),
            $watchlistItem['tmdb_id'],
            $watchlistItem['imdb_id'],
            $watchlistItem['added_at'],
        ]);
        if ($lengthOfWrittenString === false) {
            throw new RuntimeException('Could not write watch date to export csv');
        }
    }

    private function convertReleaseDate(?string $movieWatchDate) : ?Year
    {
        if (empty($movieWatchDate) === true) {
            return null;
        }

        return Year::createFromString(DateTime::createFromString($movieWatchDate)->format('Y'));
    }
}
