<?php declare(strict_types=1);

namespace Tests\Unit\Movary\JobQueue;

use Doctrine\DBAL\Connection;
use Movary\JobQueue\JobScheduleRepository;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\JobScheduleFrequency;
use Movary\ValueObject\JobType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobScheduleRepository::class)]
class JobScheduleRepositoryTest extends TestCase
{
    public function testAddPersistsSchedule() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('insert')
            ->with('job_schedule', self::callback(static function (array $data) : bool {
                return $data['job_type'] === 'tmdb_movie_sync'
                    && $data['frequency'] === 'hourly'
                    && $data['user_id'] === 42
                    && $data['next_run_at'] === '2026-10-13 13:00:00';
            }));
        $connection->method('lastInsertId')->willReturn('9');

        self::assertSame(9, (new JobScheduleRepository($connection))->add(
            JobType::createTmdbMovieSync(),
            JobScheduleFrequency::createFromString('hourly'),
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
