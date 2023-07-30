<?php declare(strict_types=1);

namespace Movary\Service\Export;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Year;

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
            throw new \RuntimeException('Could not write watch date to export csv');
        }
    }

    /** @param resource $fileHandle */
    public function writeWatchDateToCsv($fileHandle, array $movieWatchDate) : void
    {
        $releaseDate = $this->convertReleaseDate($movieWatchDate['release_date']);
        $watchDate = Date::createFromString($movieWatchDate['watched_at']);

        for ($i = 1; $i <= $movieWatchDate['plays']; $i++) {
            $lengthOfWrittenString = fputcsv($fileHandle, [
                $movieWatchDate['title'],
                $releaseDate,
                $movieWatchDate['tmdb_id'],
                $movieWatchDate['imdb_id'],
                $watchDate,
                $movieWatchDate['comment'],
            ]);

            if ($lengthOfWrittenString === false) {
                throw new \RuntimeException('Could not write watch date to export csv');
            }
        }
    }
    
    /** @param resource $fileHandle */

    public function writeWatchlistItemToCsv($filehandle, array $watchlistItem) : void
    {
        $lengthOfWrittenString = fputcsv($filehandle, [
            $watchlistItem['title'],
            $watchlistItem['tmdb_id'],
            $watchlistItem['imdb_id']
        ]);
        if ($lengthOfWrittenString === false) {
            throw new \RuntimeException('Could not write watch date to export csv');
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
