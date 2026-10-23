<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Export;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Review\MovieReviewApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Service\Export\ExportService;
use Movary\Service\Export\ExportWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExportService::class)]
#[CoversClass(ExportWriter::class)]
class ExportServiceTest extends TestCase
{
    public function testCreateExportReviewsCsvWritesDedicatedReviewShape() : void
    {
        $reviewApi = $this->createMock(MovieReviewApi::class);
        $reviewApi
            ->expects(self::once())
            ->method('fetchAllForUser')
            ->with(7)
            ->willReturn([[
                'title' => 'Arrival',
                'release_date' => '2016-11-10',
                'tmdb_id' => 329865,
                'imdb_id' => 'tt2543164',
                'review' => "Excellent, thoughtful film.\nWorth revisiting.",
                'contains_spoilers' => 1,
                'created_at' => '2026-10-20 12:00:00',
                'updated_at' => null,
            ]]);

        $movieApi = $this->createStub(MovieApi::class);
        $exportPath = tempnam(sys_get_temp_dir(), 'movary-review-export-');
        self::assertIsString($exportPath);
        $subject = new ExportService(
            $movieApi,
            $this->createStub(MovieWatchlistApi::class),
            $reviewApi,
            new ExportWriter($movieApi),
            sys_get_temp_dir() . '/',
        );

        try {
            self::assertSame($exportPath, $subject->createExportReviewsCsv(7, $exportPath));
            self::assertSame(
                "title,year,tmdbId,imdbId,review,containsSpoilers,createdAt,updatedAt\n"
                . "Arrival,2016,329865,tt2543164,\"Excellent, thoughtful film.\n"
                . "Worth revisiting.\",1,\"2026-10-20 12:00:00\",\n",
                file_get_contents($exportPath),
            );
        } finally {
            unlink($exportPath);
        }
    }
}
