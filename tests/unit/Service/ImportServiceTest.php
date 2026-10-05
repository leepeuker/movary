<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
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
        );

        self::assertSame($movie, $subject->findOrCreateMovie(123, 'Movie', 'tt123'));

        unset($subject, $jobQueueScheduler);
    }
}
