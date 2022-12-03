<?php declare(strict_types=1);

namespace Movary\Service;

use League\Csv\Reader;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\ValueObject\Date;
use Movary\ValueObject\PersonalRating;

class ImportService
{
    public function __construct(private readonly MovieApi $movieApi)
    {
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
            if (isset($record['tmdbId'], $record['imdbId'], $record['title'], $record['watchedAt']) === false) {
                throw new \RuntimeException('Import csv is missing data');
            }

            $tmdbId = (int)$record['tmdbId'];

            $movie = $this->findOrCreateMovie($tmdbId, $record['title'], $record['imdbId']);

            $this->movieApi->increaseHistoryPlaysForMovieOnDate($movie->getId(), $userId, Date::createFromString($record['watchedAt']));
        }
    }

    public function importRatings(int $userId, string $importCsvPath) : void
    {
        $csv = Reader::createFromPath($importCsvPath, 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv->getRecords() as $record) {
            if (isset($record['tmdbId'], $record['imdbId'], $record['title'], $record['userRating']) === false) {
                throw new \RuntimeException('Import csv is missing data');
            }

            $tmdbId = (int)$record['tmdbId'];

            $movie = $this->findOrCreateMovie($tmdbId, $record['title'], $record['imdbId']);

            $this->movieApi->updateUserRating($movie->getId(), $userId, PersonalRating::create((int)$record['userRating']));
        }
    }
}
