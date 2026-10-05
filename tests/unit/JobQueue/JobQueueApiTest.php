<?php declare(strict_types=1);

namespace Tests\Unit\Movary\JobQueue;

use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobQueueRepository;
use Movary\ValueObject\JobStatus;
use Movary\ValueObject\JobType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobQueueApi::class)]
class JobQueueApiTest extends TestCase
{
    public function testAddImdbSyncJobAddsMovieIdsAsParameters() : void
    {
        /** @var JobQueueRepository&MockObject $repository */
        $repository = $this->createMock(JobQueueRepository::class);
        $repository
            ->expects(self::once())
            ->method('addJob')
            ->with(
                self::callback(static fn(JobType $type) => (string)$type === 'imdb_sync'),
                self::callback(static fn(JobStatus $status) => (string)$status === 'in progress'),
                null,
                ['movieIds' => [7, 8]],
            )
            ->willReturn(5);

        (new JobQueueApi($repository))->addImdbSyncJob(JobStatus::createInProgress(), [7, 8]);
    }

    public function testAddPasswordResetEmailJobAddsWaitingJobWithoutSensitiveParameters() : void
    {
        /** @var JobQueueRepository&MockObject $repository */
        $repository = $this->createMock(JobQueueRepository::class);
        $repository
            ->expects(self::once())
            ->method('addJob')
            ->with(
                self::callback(static fn(JobType $type) => (string)$type === 'password_reset_email'),
                self::callback(static fn(JobStatus $status) => (string)$status === 'waiting'),
                12,
                ['ignoreCooldown' => true],
            )
            ->willReturn(5);

        (new JobQueueApi($repository))->addPasswordResetEmailJob(12, true);
    }

    public function testAddTmdbMovieSyncJobAddsMovieIdsAsParameters() : void
    {
        /** @var JobQueueRepository&MockObject $repository */
        $repository = $this->createMock(JobQueueRepository::class);
        $repository
            ->expects(self::once())
            ->method('addJob')
            ->with(
                self::callback(static fn(JobType $type) => (string)$type === 'tmdb_movie_sync'),
                self::callback(static fn(JobStatus $status) => (string)$status === 'waiting'),
                null,
                ['movieIds' => [7, 8]],
            )
            ->willReturn(5);

        (new JobQueueApi($repository))->addTmdbMovieSyncJob(JobStatus::createWaiting(), [7, 8]);
    }

    public function testAddTmdbPersonSyncJobAddsPersonIdsAsParameters() : void
    {
        /** @var JobQueueRepository&MockObject $repository */
        $repository = $this->createMock(JobQueueRepository::class);
        $repository
            ->expects(self::once())
            ->method('addJob')
            ->with(
                self::callback(static fn(JobType $type) => (string)$type === 'tmdb_person_sync'),
                self::callback(static fn(JobStatus $status) => (string)$status === 'in progress'),
                null,
                ['personIds' => [7, 8]],
            )
            ->willReturn(5);

        (new JobQueueApi($repository))->addTmdbPersonSyncJob(JobStatus::createInProgress(), [7, 8]);
    }

    public function testFetchJobsForStatusPageMapsJobDetails() : void
    {
        /** @var JobQueueRepository&MockObject $repository */
        $repository = $this->createMock(JobQueueRepository::class);
        $repository
            ->expects(self::once())
            ->method('fetchJobs')
            ->with(30)
            ->willReturn([[
                'id' => '7',
                'job_type' => 'tmdb_movie_sync',
                'job_status' => 'waiting',
                'user_id' => '12',
                'name' => 'Alice',
                'parameters' => '{"movieIds":[34]}',
                'updated_at' => null,
                'created_at' => '2026-10-05 12:00:00',
            ]]);

        self::assertSame(
            [[
                'id' => 7,
                'type' => 'tmdb_movie_sync',
                'status' => 'waiting',
                'userId' => 12,
                'userName' => 'Alice',
                'parameters' => ['movieIds' => [34]],
                'updatedAt' => null,
                'createdAt' => '2026-10-05 12:00:00',
            ]],
            (new JobQueueApi($repository))->fetchJobsForStatusPage(30),
        );
    }

    public function testDeleteJobReturnsRepositoryResult() : void
    {
        /** @var JobQueueRepository&MockObject $repository */
        $repository = $this->createMock(JobQueueRepository::class);
        $repository->expects(self::once())->method('deleteJob')->with(7)->willReturn(true);

        self::assertTrue((new JobQueueApi($repository))->deleteJob(7));
    }
}
