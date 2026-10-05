<?php declare(strict_types=1);

namespace Tests\Unit\Movary\JobQueue;

use Doctrine\DBAL\Connection;
use Movary\JobQueue\JobQueueFilter;
use Movary\JobQueue\JobQueueRepository;
use Movary\ValueObject\JobStatus;
use Movary\ValueObject\JobType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobQueueRepository::class)]
class JobQueueRepositoryTest extends TestCase
{
    public function testCountJobsReturnsIntegerCount() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM job_queue')
            ->willReturn('72');

        self::assertSame(72, (new JobQueueRepository($connection))->countJobs());
    }

    public function testCountJobsAppliesFilters() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchOne')
            ->with(
                'SELECT COUNT(*) FROM job_queue jobs WHERE jobs.user_id = ? AND jobs.job_type = ? AND jobs.job_status = ?',
                [12, 'tmdb_movie_sync', 'done'],
            )
            ->willReturn('4');

        $filter = JobQueueFilter::create(
            12,
            false,
            JobType::createTmdbMovieSync(),
            JobStatus::createDone(),
        );

        self::assertSame(4, (new JobQueueRepository($connection))->countJobs($filter));
    }

    public function testCountJobsCanFilterJobsWithoutUser() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM job_queue jobs WHERE jobs.user_id IS NULL', [])
            ->willReturn('3');

        self::assertSame(
            3,
            (new JobQueueRepository($connection))->countJobs(JobQueueFilter::create(withoutUser: true)),
        );
    }

    public function testFetchJobsAppliesFiltersToPagedQuery() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                self::callback(
                    static fn(string $query) : bool => str_contains(
                        $query,
                        'WHERE jobs.user_id = ? AND jobs.job_type = ? AND jobs.job_status = ?',
                    ) && str_contains($query, 'LIMIT 20 OFFSET 40'),
                ),
                [12, 'tmdb_movie_sync', 'done'],
            )
            ->willReturn([]);

        $filter = JobQueueFilter::create(
            12,
            false,
            JobType::createTmdbMovieSync(),
            JobStatus::createDone(),
        );

        self::assertSame([], (new JobQueueRepository($connection))->fetchJobs(20, 40, $filter));
    }

    public function testDeleteJobReturnsTrueWhenJobWasDeleted() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('delete')
            ->with('job_queue', ['id' => 7])
            ->willReturn(1);

        self::assertTrue((new JobQueueRepository($connection))->deleteJob(7));
    }

    public function testDeleteJobReturnsFalseWhenJobDoesNotExist() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('delete')
            ->with('job_queue', ['id' => 7])
            ->willReturn(0);

        self::assertFalse((new JobQueueRepository($connection))->deleteJob(7));
    }
}
