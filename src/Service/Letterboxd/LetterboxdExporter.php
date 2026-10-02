<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd;

use Doctrine\DBAL\Connection;
use League\Csv\Writer;
use Movary\Util\File;
use Movary\ValueObject\DateTime;
use RuntimeException;
use Throwable;
use Traversable;
use ZipArchive;

class LetterboxdExporter
{
    private const array CSV_HEADER = ['WatchedDate', 'Title', 'Year', 'tmdbID', 'Rating10'];

    private const int LIMIT_CSV_FILE_RECORDS = 1000;

    private const int MAX_ZIP_FILE_SIZE_IN_BYTES = 32 * 1024 * 1024;

    public function __construct(
        private readonly Connection $dbConnection,
        private readonly File $fileUtil,
    ) {
    }

    public function generateZip(int $userId) : string
    {
        $zipFilePath = $this->fileUtil->createTmpFile();
        $csvFilePaths = [];
        $zip = new ZipArchive();
        $zipIsOpen = false;

        try {
            $openResult = $zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($openResult !== true) {
                throw new RuntimeException('Could not create Letterboxd export ZIP. Error code: ' . $openResult);
            }
            $zipIsOpen = true;

            foreach ($this->generateCsvFiles($userId) as $index => $csvFilePath) {
                $csvFilePaths[] = $csvFilePath;

                if ($zip->addFile($csvFilePath, 'export-' . $index . '.csv') === false) {
                    throw new RuntimeException('Could not add CSV file to Letterboxd export ZIP.');
                }
            }

            $closeResult = $zip->close();
            $zipIsOpen = false;
            if ($closeResult === false) {
                throw new RuntimeException('Could not finish Letterboxd export ZIP.');
            }

            return $this->readZipContent($zipFilePath);
        } catch (Throwable $error) {
            if ($zipIsOpen === true) {
                $zip->close();
            }

            throw $error;
        } finally {
            if (is_file($zipFilePath) === true) {
                unlink($zipFilePath);
            }

            foreach ($csvFilePaths as $csvFilePath) {
                if (is_file($csvFilePath) === true) {
                    unlink($csvFilePath);
                }
            }
        }
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
        try {
            $csv = $this->createCsvWriter($csvFilePath);

            // csv format documentation here https://letterboxd.com/about/importing-data/
            $csv->insertOne(self::CSV_HEADER);

            $csvLineCounter = 0;
            foreach ($stmt->iterateAssociative() as $row) {
                if ($csvLineCounter >= self::LIMIT_CSV_FILE_RECORDS) {
                    yield $csvFilePath;

                    $csvFilePath = $this->fileUtil->createTmpFile();
                    $csv = $this->createCsvWriter($csvFilePath);
                    $csv->insertOne(self::CSV_HEADER);

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
        } catch (Throwable $error) {
            if (is_file($csvFilePath) === true) {
                unlink($csvFilePath);
            }

            throw $error;
        }
    }

    private function createCsvWriter(string $csvFilePath) : Writer
    {
        $csv = Writer::from($csvFilePath);
        $csv->setDelimiter(',');

        return $csv;
    }

    private function readZipContent(string $zipFilePath) : string
    {
        $zipFileSize = filesize($zipFilePath);
        if ($zipFileSize === false) {
            throw new RuntimeException('Could not determine Letterboxd export ZIP size.');
        }
        if ($zipFileSize > self::MAX_ZIP_FILE_SIZE_IN_BYTES) {
            throw new RuntimeException('Letterboxd export ZIP exceeds the maximum size of 32 MB.');
        }

        $zipContent = file_get_contents($zipFilePath);
        if ($zipContent === false) {
            throw new RuntimeException('Could not read Letterboxd export ZIP.');
        }

        return $zipContent;
    }
}
