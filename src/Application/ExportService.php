<?php declare(strict_types=1);

namespace Movary\Application;

use Movary\Application\Movie\Api;
use Movary\ValueObject\DateTime;

class ExportService
{
    public function __construct(private readonly Api $movieApi)
    {
    }

    public function getHistoryCsv(int $userId) : string
    {
        $movies = $this->movieApi->fetchHistoryOrderedByWatchedAtDesc($userId);

        $csv = 'title,year,tmdbId,imdbId,watchedAt' . PHP_EOL;

        foreach ($movies as $movie) {
            $csv .= sprintf(
                '"%s",%s,%s,%s,%s' . PHP_EOL,
                $movie['title'],
                DateTime::createFromString($movie['release_date'])->format('Y'),
                $movie['tmdb_id'],
                $movie['imdb_id'],
                $movie['watched_at'],
            );
        }

        return $csv;
    }

    public function getRatingCsv(int $userId) : string
    {
        $movies = $this->movieApi->fetchAll();

        $csv = 'title,year,tmdbId,imdbId,userRating' . PHP_EOL;

        foreach ($movies as $movie) {
            $userRating = $this->movieApi->findUserRating($movie->getId(), $userId);

            if ($userRating === null) {
                continue;
            }

            $csv .= sprintf(
                '"%s",%s,%s,%s,%s' . PHP_EOL,
                $movie->getTitle(),
                (string)$movie->getReleaseDate()?->format('Y'),
                $movie->getTmdbId(),
                (string)$movie->getImdbId(),
                (string)$userRating,
            );
        }

        return $csv;
    }
}
