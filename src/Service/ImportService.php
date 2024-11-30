<?php declare(strict_types=1);

namespace Movary\Service;

use League\Csv\Reader;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\PersonalRating;
use RuntimeException;

class ImportService
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly MovieWatchlistApi $watchlistApi,
    ) {
    }

    public function findOrCreateMovie(int $tmdbId, string $title, ?string $imdbId) : MovieEntity
    {
        $movie = $this->movieApi->findByTmdbId($tmdbId);

        if ($movie === null) {
            $movie = $this->movieApi->create(
                title: $title,
                tmdbId: $tmdbId,
                imdbId: $imdbId,
            );
        }

        return $movie;
    }

    public function importHistory(int $userId, string $importCsvPath) : void
    {
        $csv = Reader::createFromPath($importCsvPath, 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv->getRecords() as $record) {
            if (isset($record['tmdbId'], $record['imdbId'], $record['title']) === false || array_key_exists('watchedAt', $record) === false) {
                throw new RuntimeException('Import csv is missing data');
            }

            $tmdbId = (int)$record['tmdbId'];
            $watchDate = empty($record['watchedAt']) === false ? Date::createFromString($record['watchedAt']) : null;

            $movie = $this->findOrCreateMovie($tmdbId, (string)$record['title'], (string)$record['imdbId']);

            $this->movieApi->addPlaysForMovieOnDate($movie->getId(), $userId, $watchDate);

            if (empty($record['comment']) === false) {
                $this->movieApi->updateHistoryComment($movie->getId(), $userId, $watchDate, $record['comment']);
            }

            if (empty($record['location']) === false) {
                $this->movieApi->updateHistoryLocationByName($movie->getId(), $userId, $watchDate, $record['location']);
            }
        }
    }

    public function importRatings(int $userId, string $importCsvPath) : void
    {
        $csv = Reader::createFromPath($importCsvPath, 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv->getRecords() as $record) {
            if (isset($record['tmdbId'], $record['imdbId'], $record['title'], $record['userRating']) === false) {
                throw new RuntimeException('Import csv is missing data');
            }

            $tmdbId = (int)$record['tmdbId'];

            $movie = $this->findOrCreateMovie($tmdbId, (string)$record['title'], (string)$record['imdbId']);

            $this->movieApi->updateUserRating($movie->getId(), $userId, PersonalRating::create((int)$record['userRating']));
        }
    }

    public function importWatchlist(int $userId, string $importCsvPath) : void
    {
        $csv = Reader::createFromPath($importCsvPath, 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv->getRecords() as $record) {
            if (isset($record['title'], $record['tmdbId'], $record['imdbId'], $record['addedAt']) === false) {
                throw new RuntimeException('Import csv is missing data');
            }

            $movie = $this->findOrCreateMovie((int)$record['tmdbId'], (string)$record['title'], (string)$record['imdbId']);

            $this->watchlistApi->addMovieToWatchlist($userId, $movie->getId(), DateTime::createFromString((string)$record['addedAt']));
        }
    }
}
