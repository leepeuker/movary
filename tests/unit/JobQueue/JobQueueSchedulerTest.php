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
        for ($movieId = 1; $movieId <= 100; $movieId++) {
            $subject->storeMovieIdForTmdbSyncJob($movieId);
        }
        $subject->storeMovieIdForTmdbSyncJob(100);
        $subject->storeMovieIdForTmdbSyncJob(101);

        unset($subject);

        self::assertSame(range(1, 100), $scheduledBatches[0]);
        self::assertSame([101], $scheduledBatches[1]);
    }
}
