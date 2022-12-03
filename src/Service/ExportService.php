<?php declare(strict_types=1);

namespace Movary\Service;

use Movary\Domain\Movie\MovieApi;
use Movary\ValueObject\DateTime;

class ExportService
{
    public function __construct(private readonly MovieApi $movieApi)
    {
    }

    public function getHistoryCsv(int $userId) : string
    {
        $movieWatchDates = $this->movieApi->fetchHistoryOrderedByWatchedAtDesc($userId);

        $csv = 'title,year,tmdbId,imdbId,watchedAt' . PHP_EOL;

        foreach ($movieWatchDates as $movieWatchDate) {
            for ($i = 1; $i <= $movieWatchDate['plays']; $i++) {
                $csv .= sprintf(
                    '"%s",%s,%s,%s,%s' . PHP_EOL,
                    $movieWatchDate['title'],
                    DateTime::createFromString($movieWatchDate['release_date'])->format('Y'),
                    $movieWatchDate['tmdb_id'],
                    $movieWatchDate['imdb_id'],
                    $movieWatchDate['watched_at'],
                );
            }
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
