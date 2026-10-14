<?php declare(strict_types=1);

namespace Tests\Unit\Movary\JobQueue;

use Doctrine\DBAL\Connection;
use Movary\JobQueue\JobScheduleFilter;
use Movary\JobQueue\JobScheduleRepository;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\JobScheduleCronExpression;
use Movary\ValueObject\JobType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobScheduleRepository::class)]
class JobScheduleRepositoryTest extends TestCase
{
    public function testDeleteAllRemovesEverySchedule() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('executeStatement')
            ->with('DELETE FROM job_schedule');

        (new JobScheduleRepository($connection))->deleteAll();
    }

    public function testCountSchedulesAppliesFilters() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchOne')
            ->with(
                'SELECT COUNT(*) FROM job_schedule schedules WHERE schedules.user_id = ? '
                . 'AND schedules.job_type = ? AND schedules.enabled = ?',
                [12, 'tmdb_movie_sync', 0],
            )
            ->willReturn('4');

        $filter = JobScheduleFilter::create(12, false, JobType::createTmdbMovieSync(), false);

        self::assertSame(4, (new JobScheduleRepository($connection))->countSchedules($filter));
    }

    public function testFetchSchedulesAppliesFiltersToPagedQuery() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                self::callback(static fn(string $query) : bool => str_contains(
                    $query,
                    'WHERE schedules.user_id IS NULL AND schedules.job_type = ? AND schedules.enabled = ?',
                ) && str_contains($query, 'LIMIT 20 OFFSET 40')),
                ['tmdb_person_sync', 1],
            )
            ->willReturn([]);

        $filter = JobScheduleFilter::create(
            withoutUser: true,
            type: JobType::createTmdbPersonSync(),
            enabled: true,
        );

        self::assertSame([], (new JobScheduleRepository($connection))->fetchSchedules(20, 40, $filter));
    }

    public function testAddPersistsSchedule() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('insert')
            ->with('job_schedule', self::callback(static function (array $data) : bool {
                return $data['job_type'] === 'tmdb_movie_sync'
                    && $data['cron_expression'] === '0 * * * *'
                    && $data['user_id'] === 42
                    && $data['next_run_at'] === '2026-10-13 13:00:00';
            }));
        $connection->method('lastInsertId')->willReturn('9');

        self::assertSame(9, (new JobScheduleRepository($connection))->add(
            JobType::createTmdbMovieSync(),
            JobScheduleCronExpression::createFromString('0 * * * *'),
            ['threshold' => 100],
            DateTime::createFromString('2026-10-13 13:00:00'),
            42,
        ));
    }

    public function testUpdateNextRunUsesCompareAndSet() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('executeStatement')
            ->with(
                'UPDATE job_schedule SET next_run_at = ?, updated_at = ? '
                . 'WHERE id = ? AND enabled = ? AND next_run_at = ?',
                ['2026-10-14 12:00:00', '2026-10-13 12:00:00', 9, 1, '2026-10-13 11:00:00'],
            )
            ->willReturn(1);

        self::assertTrue((new JobScheduleRepository($connection))->updateNextRunIfCurrent(
            9,
            DateTime::createFromString('2026-10-13 11:00:00'),
            DateTime::createFromString('2026-10-14 12:00:00'),
            DateTime::createFromString('2026-10-13 12:00:00'),
        ));
    }
}
