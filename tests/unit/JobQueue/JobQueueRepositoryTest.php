<?php declare(strict_types=1);

namespace Tests\Unit\Movary\JobQueue;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
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

    public function testHasJobWithStatusesReturnsWhetherMatchingJobExists() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchOne')
            ->with(
                'SELECT COUNT(*) FROM job_queue WHERE job_type = ? AND job_status IN (?, ?)',
                self::callback(static function (array $parameters) : bool {
                    return array_map('strval', $parameters) === [
                        'tmdb_movie_sync',
                        'waiting',
                        'in progress',
                    ];
                }),
            )
            ->willReturn('1');

        self::assertTrue((new JobQueueRepository($connection))->hasJobWithStatuses(
            JobType::createTmdbMovieSync(),
            [JobStatus::createWaiting(), JobStatus::createInProgress()],
        ));
    }

    public function testAddJobUnlessExistsWithStatusesChecksAndInsertsWhileHoldingTypeLock() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new SqlitePlatform());
        $connection
            ->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(static fn(callable $callback) : mixed => $callback($connection));
        $connection
            ->expects(self::once())
            ->method('executeStatement')
            ->with(
                'UPDATE job_queue SET job_type = job_type WHERE job_type = ?',
                self::callback(static fn(array $parameters) => (string)$parameters[0] === 'tmdb_movie_sync'),
            );
        $connection->expects(self::once())->method('fetchOne')->willReturn('0');
        $connection->expects(self::once())->method('insert');
        $connection->expects(self::once())->method('lastInsertId')->willReturn('5');

        self::assertSame(5, (new JobQueueRepository($connection))->addJobUnlessExistsWithStatuses(
            JobType::createTmdbMovieSync(),
            JobStatus::createWaiting(),
            [JobStatus::createWaiting(), JobStatus::createInProgress()],
        ));
    }

    public function testAddJobUnlessExistsWithStatusesDoesNotInsertDuplicate() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new SqlitePlatform());
        $connection
            ->method('transactional')
            ->willReturnCallback(static fn(callable $callback) : mixed => $callback($connection));
        $connection->expects(self::once())->method('executeStatement');
        $connection->expects(self::once())->method('fetchOne')->willReturn('1');
        $connection->expects(self::never())->method('insert');

        self::assertNull((new JobQueueRepository($connection))->addJobUnlessExistsWithStatuses(
            JobType::createTmdbMovieSync(),
            JobStatus::createWaiting(),
            [JobStatus::createWaiting(), JobStatus::createInProgress()],
        ));
    }

    public function testAddJobUnlessExistsWithStatusesUsesMysqlAdvisoryLock() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $connection
            ->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(static fn(callable $callback) : mixed => $callback($connection));
        $connection
            ->expects(self::exactly(3))
            ->method('fetchOne')
            ->willReturnCallback(static function (string $query, array $parameters) : string {
                return match ($query) {
                    'SELECT GET_LOCK(?, 10)', 'SELECT RELEASE_LOCK(?)' => self::assertLockParameters($parameters),
                    'SELECT COUNT(*) FROM job_queue WHERE job_type = ? AND job_status IN (?, ?)' =>
                        self::assertJobStatusParameters($parameters),
                    default => self::fail('Unexpected query: ' . $query),
                };
            });
        $connection->expects(self::once())->method('insert');
        $connection->expects(self::once())->method('lastInsertId')->willReturn('5');

        self::assertSame(5, (new JobQueueRepository($connection))->addJobUnlessExistsWithStatuses(
            JobType::createTmdbMovieSync(),
            JobStatus::createWaiting(),
            [JobStatus::createWaiting(), JobStatus::createInProgress()],
        ));
    }

    public function testAddJobUnlessExistsForUserWithStatusesDoesNotInsertDuplicate() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new SqlitePlatform());
        $connection
            ->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(static fn(callable $callback) : mixed => $callback($connection));
        $connection
            ->expects(self::once())
            ->method('executeStatement')
            ->with(
                'UPDATE job_queue SET job_type = job_type WHERE job_type = ? AND user_id = ?',
                self::callback(
                    static fn(array $parameters) : bool => (string)$parameters[0] === 'email_verification'
                        && $parameters[1] === 12,
                ),
            );
        $connection
            ->expects(self::once())
            ->method('fetchOne')
            ->with(
                'SELECT COUNT(*) FROM job_queue WHERE job_type = ? AND user_id = ? '
                    . 'AND job_status IN (?, ?)',
                self::callback(
                    static fn(array $parameters) : bool => array_map('strval', $parameters) === [
                        'email_verification',
                        '12',
                        'waiting',
                        'in progress',
                    ],
                ),
            )
            ->willReturn('1');
        $connection->expects(self::never())->method('insert');

        self::assertNull((new JobQueueRepository($connection))->addJobUnlessExistsForUserWithStatuses(
            JobType::createEmailVerification(),
            JobStatus::createWaiting(),
            [JobStatus::createWaiting(), JobStatus::createInProgress()],
            12,
            ['ignoreCooldown' => false],
        ));
    }

    public function testAddJobUnlessExistsForUserWithStatusesUsesMysqlUserLock() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn(new MySQLPlatform());
        $connection
            ->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(static fn(callable $callback) : mixed => $callback($connection));
        $connection
            ->expects(self::exactly(3))
            ->method('fetchOne')
            ->willReturnCallback(static function (string $query, array $parameters) : string {
                if ($query === 'SELECT GET_LOCK(?, 10)' || $query === 'SELECT RELEASE_LOCK(?)') {
                    self::assertSame('movary:job-queue:email_verification:12', (string)$parameters[0]);

                    return '1';
                }

                self::assertSame(
                    'SELECT COUNT(*) FROM job_queue WHERE job_type = ? AND user_id = ? '
                        . 'AND job_status IN (?, ?)',
                    $query,
                );
                self::assertSame(
                    ['email_verification', '12', 'waiting', 'in progress'],
                    array_map('strval', $parameters),
                );

                return '0';
            });
        $connection->expects(self::once())->method('insert');
        $connection->expects(self::once())->method('lastInsertId')->willReturn('5');

        self::assertSame(5, (new JobQueueRepository($connection))->addJobUnlessExistsForUserWithStatuses(
            JobType::createEmailVerification(),
            JobStatus::createWaiting(),
            [JobStatus::createWaiting(), JobStatus::createInProgress()],
            12,
            ['ignoreCooldown' => false],
        ));
    }

    private static function assertLockParameters(array $parameters) : string
    {
        self::assertSame('movary:job-queue:tmdb_movie_sync', (string)$parameters[0]);

        return '1';
    }

    private static function assertJobStatusParameters(array $parameters) : string
    {
        self::assertSame(['tmdb_movie_sync', 'waiting', 'in progress'], array_map('strval', $parameters));

        return '0';
    }
}
