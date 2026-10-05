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
}
