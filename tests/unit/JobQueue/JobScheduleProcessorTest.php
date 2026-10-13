<?php declare(strict_types=1);

namespace Tests\Unit\Movary\JobQueue;

use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobScheduleEntity;
use Movary\JobQueue\JobScheduleProcessor;
use Movary\JobQueue\JobScheduleRepository;
use Movary\Util\Json;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobScheduleProcessor::class)]
class JobScheduleProcessorTest extends TestCase
{
    public function testEnqueueDueJobsCreatesJobAndAdvancesSchedule() : void
    {
        $currentDateTime = DateTime::createFromString('2026-10-13 12:02:30');
        $schedule = JobScheduleEntity::createFromArray([
            'id' => 9,
            'job_type' => 'tmdb_movie_sync',
            'cron_expression' => '* * * * *',
            'user_id' => 42,
            'parameters' => Json::encode([
                'ids' => [7, 8],
                'maxAgeInHours' => 48,
                'threshold' => 100,
                'onlyNeverSynced' => true,
            ]),
            'enabled' => 1,
            'next_run_at' => '2026-10-13 12:00:00',
        ]);
        $repository = $this->createMock(JobScheduleRepository::class);
        $repository->expects(self::once())->method('fetchDue')->with($currentDateTime)->willReturn([$schedule]);
        $repository
            ->expects(self::once())
            ->method('updateNextRunIfCurrent')
            ->with(
                9,
                self::callback(static fn(DateTime $dateTime) => (string)$dateTime === '2026-10-13 12:00:00'),
                self::callback(static fn(DateTime $dateTime) => (string)$dateTime === '2026-10-13 12:03:00'),
                $currentDateTime,
            );
        $jobQueueApi = $this->createMock(JobQueueApi::class);
        $jobQueueApi
            ->expects(self::once())
            ->method('addTmdbMovieSyncJobUnlessActive')
            ->with([7, 8], 48, 100, true, 42)
            ->willReturn(23);

        (new JobScheduleProcessor($repository, $jobQueueApi))->enqueueDueJobs($currentDateTime);
    }
}
