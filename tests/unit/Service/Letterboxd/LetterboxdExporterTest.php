<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Letterboxd;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Movary\Service\Letterboxd\LetterboxdExporter;
use Movary\Util\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Traversable;
use ZipArchive;

#[CoversClass(LetterboxdExporter::class)]
class LetterboxdExporterTest extends TestCase
{
    public function testGenerateZipFileCreatesSplitCsvFilesAndRemovesTemporaryCsvFiles() : void
    {
        $rows = [];
        for ($index = 0; $index < 1001; $index++) {
            $rows[] = [
                'watched_at' => '2026-01-01',
                'title' => 'Movie ' . $index,
                'release_date' => null,
                'tmdb_id' => $index + 1,
                'rating' => null,
            ];
        }

        $zipFilePath = $this->createTemporaryFile();
        $firstCsvFilePath = $this->createTemporaryFile();
        $secondCsvFilePath = $this->createTemporaryFile();
        $fileUtil = $this->createStub(File::class);
        $fileUtil->method('createTmpFile')->willReturnOnConsecutiveCalls(
            $zipFilePath,
            $firstCsvFilePath,
            $secondCsvFilePath,
        );
        $subject = $this->createExporter($rows, $fileUtil);

        try {
            self::assertSame($zipFilePath, $subject->generateZipFile(42));
            self::assertFileDoesNotExist($firstCsvFilePath);
            self::assertFileDoesNotExist($secondCsvFilePath);

            $zip = new ZipArchive();
            self::assertTrue($zip->open($zipFilePath));
            self::assertSame(2, $zip->numFiles);

            $firstCsv = $zip->getFromName('export-0.csv');
            $secondCsv = $zip->getFromName('export-1.csv');
            self::assertIsString($firstCsv);
            self::assertIsString($secondCsv);
            self::assertStringStartsWith('WatchedDate,Title,Year,tmdbID,Rating10', $firstCsv);
            self::assertStringContainsString('Movie 999', $firstCsv);
            self::assertStringStartsWith('2026-01-01,"Movie 1000",,1001,', $secondCsv);
            self::assertStringContainsString('Movie 1000', $secondCsv);
            $zip->close();
        } finally {
            if (is_file($zipFilePath)) {
                unlink($zipFilePath);
            }
        }
    }

    public function testGenerateZipFileCreatesHeaderForEmptyHistory() : void
    {
        $subject = $this->createExporter([]);
        $zipFilePath = $subject->generateZipFile(42);

        try {
            $zip = new ZipArchive();
            self::assertTrue($zip->open($zipFilePath));
            self::assertSame(1, $zip->numFiles);
            self::assertSame(
                "WatchedDate,Title,Year,tmdbID,Rating10\n",
                $zip->getFromName('export-0.csv'),
            );
            $zip->close();
        } finally {
            if (is_file($zipFilePath)) {
                unlink($zipFilePath);
            }
        }
    }

    public function testGenerateZipFileRemovesTemporaryFilesWhenCsvGenerationFails() : void
    {
        $zipFilePath = $this->createTemporaryFile();
        $csvFilePath = $this->createTemporaryFile();
        $fileUtil = $this->createStub(File::class);
        $fileUtil->method('createTmpFile')->willReturnOnConsecutiveCalls($zipFilePath, $csvFilePath);
        $result = $this->createStub(Result::class);
        $result->method('iterateAssociative')->willReturnCallback(
            static function () : Traversable {
                throw new RuntimeException('Database read failed.');
                yield;
            },
        );
        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($result);
        $subject = new LetterboxdExporter($connection, $fileUtil);

        try {
            $subject->generateZipFile(42);
            self::fail('Expected export generation to fail.');
        } catch (RuntimeException $error) {
            self::assertSame('Database read failed.', $error->getMessage());
        }

        self::assertFileDoesNotExist($zipFilePath);
        self::assertFileDoesNotExist($csvFilePath);
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function createExporter(array $rows, ?File $fileUtil = null) : LetterboxdExporter
    {
        $result = $this->createStub(Result::class);
        $result->method('iterateAssociative')->willReturnCallback(
            static function () use ($rows) : Traversable {
                yield from $rows;
            },
        );

        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($result);

        return new LetterboxdExporter($connection, $fileUtil ?? new File());
    }

    private function createTemporaryFile() : string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'movary-test-');
        self::assertIsString($filePath);

        return $filePath;
    }
}
