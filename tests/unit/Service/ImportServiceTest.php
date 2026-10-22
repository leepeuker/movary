<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\Movie\Review\MovieReviewApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobQueueScheduler;
use Movary\Service\ImportService;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\JobStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImportService::class)]
class ImportServiceTest extends TestCase
{
    public function testImportReviewsCreatesReviewWithoutHistoryOrRating() : void
    {
        $importPath = tempnam(sys_get_temp_dir(), 'movary-review-import-');
        self::assertIsString($importPath);
        file_put_contents(
            $importPath,
            "title,year,tmdbId,imdbId,review,containsSpoilers,createdAt,updatedAt\n"
            . "Arrival,2016,329865,tt2543164,Excellent,true,2026-10-20 12:00:00,\n",
        );

        $movie = $this->createStub(MovieEntity::class);
        $movie->method('getId')->willReturn(42);
        $movie->method('getUpdatedAtTmdb')->willReturn(DateTime::createFromString('2026-10-20 10:00:00'));
        $movieApi = $this->createMock(MovieApi::class);
        $movieApi->expects(self::once())->method('findByTmdbId')->with(329865)->willReturn($movie);
        $reviewApi = $this->createMock(MovieReviewApi::class);
        $reviewApi
            ->expects(self::once())
            ->method('import')
            ->with(
                42,
                7,
                'Excellent',
                true,
                self::callback(static fn(DateTime $date) : bool => (string)$date === '2026-10-20 12:00:00'),
                null,
            );

        $subject = new ImportService(
            $movieApi,
            $this->createStub(MovieWatchlistApi::class),
            new JobQueueScheduler($this->createStub(JobQueueApi::class), false),
            $reviewApi,
        );

        try {
            $subject->importReviews(7, $importPath);
        } finally {
            unlink($importPath);
            unset($subject);
        }
    }

    public function testFindOrCreateMovieDoesNotScheduleTmdbSyncForSyncedMovie() : void
    {
        $movie = $this->createStub(MovieEntity::class);
        $movie->method('getUpdatedAtTmdb')->willReturn(DateTime::createFromString('2026-10-05 12:00:00'));

        $movieApi = $this->createMock(MovieApi::class);
        $movieApi->expects(self::once())->method('findByTmdbId')->with(123)->willReturn($movie);
        $movieApi->expects(self::never())->method('create');

        $jobQueueApi = $this->createMock(JobQueueApi::class);
        $jobQueueApi->expects(self::never())->method('addTmdbMovieSyncJob');
        $jobQueueScheduler = new JobQueueScheduler($jobQueueApi, false);
        $subject = new ImportService(
            $movieApi,
            $this->createStub(MovieWatchlistApi::class),
            $jobQueueScheduler,
            $this->createStub(MovieReviewApi::class),
        );

        self::assertSame($movie, $subject->findOrCreateMovie(123, 'Movie', 'tt123'));

        unset($subject, $jobQueueScheduler);
    }

    public function testFindOrCreateMovieSchedulesTmdbSyncForNewMovie() : void
    {
        $movie = $this->createStub(MovieEntity::class);
        $movie->method('getId')->willReturn(42);
        $movie->method('getUpdatedAtTmdb')->willReturn(null);

        $movieApi = $this->createMock(MovieApi::class);
        $movieApi->expects(self::once())->method('findByTmdbId')->with(123)->willReturn(null);
        $movieApi
            ->expects(self::once())
            ->method('create')
            ->with('Movie', 123, null, null, null, null, null, null, null, null, null, null, 'tt123')
            ->willReturn($movie);

        $jobQueueApi = $this->createMock(JobQueueApi::class);
        $jobQueueApi
            ->expects(self::once())
            ->method('addTmdbMovieSyncJob')
            ->with(
                self::callback(static fn(JobStatus $status) => (string)$status === 'waiting'),
                [42],
            )
            ->willReturn(1);
        $jobQueueScheduler = new JobQueueScheduler($jobQueueApi, false);
        $subject = new ImportService(
            $movieApi,
            $this->createStub(MovieWatchlistApi::class),
            $jobQueueScheduler,
            $this->createStub(MovieReviewApi::class),
        );

        self::assertSame($movie, $subject->findOrCreateMovie(123, 'Movie', 'tt123'));

        unset($subject, $jobQueueScheduler);
    }
}
