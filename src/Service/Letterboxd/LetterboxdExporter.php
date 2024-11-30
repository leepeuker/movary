<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd;

use Doctrine\DBAL\Connection;
use League\Csv\Writer;
use Movary\Util\File;
use Movary\ValueObject\DateTime;
use Traversable;

class LetterboxdExporter
{
    private const int LIMIT_CSV_FILE_RECORDS = 1000;

    public function __construct(
        private readonly Connection $dbConnection,
        private readonly File $fileUtil,
    ) {
    }

    public function generateCsvFiles(int $userId) : Traversable
    {
        $stmt = $this->dbConnection->executeQuery(
            'SELECT m.title, m.release_date, m.tmdb_id, mw.watched_at, mur.rating
            FROM movie_user_watch_dates mw
            JOIN movie m on mw.movie_id = m.id
            LEFT JOIN movie_user_rating mur on m.id = mur.movie_id AND mur.user_id = ?
            WHERE mw.user_id = ?
            ORDER BY mw.watched_at',
            [$userId, $userId],
        );

        $csvFilePath = $this->fileUtil->createTmpFile();
        $csv = $this->createCsvWriter($csvFilePath);

        // csv format documentation here https://letterboxd.com/about/importing-data/
        $csv->insertOne(['WatchedDate', 'Title', 'Year', 'tmdbID', 'Rating10']);

        $csvLineCounter = 0;
        foreach ($stmt->iterateAssociative() as $row) {
            if ($csvLineCounter >= self::LIMIT_CSV_FILE_RECORDS) {
                yield $csvFilePath;

                $csvFilePath = $this->fileUtil->createTmpFile();
                $csv = $this->createCsvWriter($csvFilePath);

                $csvLineCounter = 0;
            }

            $releaseYear = null;
            if (empty($row['release_date']) === false) {
                $releaseYear = DateTime::createFromString($row['release_date'])->format('Y');
            }

            $csv->insertOne([$row['watched_at'], $row['title'], $releaseYear, $row['tmdb_id'], $row['rating']]);

            $csvLineCounter++;
        }

        yield $csvFilePath;
    }

    private function createCsvWriter(string $csvFilePath) : Writer
    {
        $csv = Writer::createFromPath($csvFilePath);
        $csv->setDelimiter(',');

        return $csv;
    }
}
