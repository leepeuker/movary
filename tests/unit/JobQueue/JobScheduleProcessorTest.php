<?php declare(strict_types=1);

namespace Tests\Unit\Movary\JobQueue;

use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobScheduleEntity;
use Movary\JobQueue\JobScheduleProcessor;
use Movary\JobQueue\JobScheduleRepository;
use Movary\JobQueue\TmdbImageCacheJobParameters;
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

    public function testEnqueueDueJobsDispatchesImdbAndImageCacheSchedules() : void
    {
        $currentDateTime = DateTime::createFromString('2026-10-13 12:02:30');
        $imdbSchedule = JobScheduleEntity::createFromArray([
            'id' => 10,
            'job_type' => 'imdb_sync',
            'cron_expression' => '0 * * * *',
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
        $imageSchedule = JobScheduleEntity::createFromArray([
            'id' => 11,
            'job_type' => 'tmdb_image_cache',
            'cron_expression' => '0 * * * *',
            'user_id' => 42,
            'parameters' => Json::encode([
                'movieIds' => [7],
                'personIds' => [8],
            ]),
            'enabled' => 1,
            'next_run_at' => '2026-10-13 12:00:00',
        ]);
        $repository = $this->createMock(JobScheduleRepository::class);
        $repository->method('fetchDue')->willReturn([$imdbSchedule, $imageSchedule]);
        $repository->expects(self::exactly(2))->method('updateNextRunIfCurrent');
        $jobQueueApi = $this->createMock(JobQueueApi::class);
        $jobQueueApi
            ->expects(self::once())
            ->method('addImdbSyncJobUnlessActive')
            ->with([7, 8], 48, 100, true, 42);
        $jobQueueApi
            ->expects(self::once())
            ->method('addTmdbImageCacheRefreshJobUnlessActive')
            ->with(
                self::callback(static fn(TmdbImageCacheJobParameters $parameters) : bool =>
                    $parameters->getMovieIds() === [7]
                    && $parameters->getPersonIds() === [8]),
                42,
            );

        (new JobScheduleProcessor($repository, $jobQueueApi))->enqueueDueJobs($currentDateTime);
    }
}
