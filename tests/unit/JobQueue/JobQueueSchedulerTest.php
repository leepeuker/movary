<?php declare(strict_types=1);

namespace Tests\Unit\Movary\JobQueue;

use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobQueueScheduler;
use Movary\ValueObject\JobStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobQueueScheduler::class)]
class JobQueueSchedulerTest extends TestCase
{
    public function testPosterRefreshIsQueuedWhenImageCachingIsEnabled() : void
    {
        $jobQueueApi = $this->createMock(JobQueueApi::class);
        $jobQueueApi
            ->expects(self::once())
            ->method('addTmdbImageCacheJob')
            ->with([12], []);

        $subject = new JobQueueScheduler($jobQueueApi, true);
        $subject->storeMovieIdForTmdbImageCacheJob(12);
        unset($subject);
    }

    public function testPosterRefreshIsNotQueuedWhenImageCachingIsDisabled() : void
    {
        $jobQueueApi = $this->createMock(JobQueueApi::class);
        $jobQueueApi->expects(self::never())->method('addTmdbImageCacheJob');

        $subject = new JobQueueScheduler($jobQueueApi, false);
        $subject->storeMovieIdForTmdbImageCacheJob(12);
        unset($subject);
    }

    public function testTmdbMovieSyncJobsAreDeduplicatedAndBatched() : void
    {
        $scheduledBatches = [];
        $jobQueueApi = $this->createMock(JobQueueApi::class);
        $jobQueueApi
            ->expects(self::exactly(2))
            ->method('addTmdbMovieSyncJob')
            ->willReturnCallback(
                static function (JobStatus $status, array $movieIds) use (&$scheduledBatches) : int {
                    self::assertSame('waiting', (string)$status);
                    $scheduledBatches[] = $movieIds;

                    return count($scheduledBatches);
                },
            );

        $subject = new JobQueueScheduler($jobQueueApi, false);
        for ($movieId = 1; $movieId <= 250; $movieId++) {
            $subject->storeMovieIdForTmdbSyncJob($movieId);
        }
        $subject->storeMovieIdForTmdbSyncJob(250);
        $subject->storeMovieIdForTmdbSyncJob(251);

        unset($subject);

        self::assertSame(range(1, 250), $scheduledBatches[0]);
        self::assertSame([251], $scheduledBatches[1]);
    }
}
