<?php declare(strict_types=1);

namespace Movary\Application;

use Movary\Application\Movie\Api;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;

class ExportService
{
    public function __construct(private readonly Api $movieApi)
    {
    }

    public function getHistoryCsv() : string
    {
        $movies = $this->movieApi->fetchHistoryOrderedByWatchedAtDesc();

        $csv = 'title,year,watchedAt,tmdbId,imdbId' . PHP_EOL;

        foreach ($movies as $movie) {
            $csv .= sprintf(
                '"%s",%s,%s,%s,%s' . PHP_EOL,
                $movie['title'],
                DateTime::createFromString($movie['release_date'])->format('Y'),
                $movie['watched_at'],
                $movie['tmdb_id'],
                $movie['imdb_id'],
            );
        }

        return $csv;
    }

    public function getRatingCsv() : string
    {
        $movies = $this->movieApi->fetchAll();

        $csv = 'title,year,personalRating,tmdbId,imdbId' . PHP_EOL;

        foreach ($movies as $movie) {
            if ($movie->getUserRating() === null) {
                continue;
            }

            $csv .= sprintf(
                '"%s",%s,%s,%s,%s' . PHP_EOL,
                $movie->getTitle(),
                (string)$movie->getReleaseDate()?->format('Y'),
                (string)$movie->getUserRating(),
                $movie->getTmdbId(),
                (string)$movie->getImdbId()
            );
        }

        return $csv;
    }
}
