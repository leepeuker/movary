<?php declare(strict_types=1);

namespace Tests\Unit\Movary\JobQueue;

use Doctrine\DBAL\Connection;
use Movary\JobQueue\JobQueueRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobQueueRepository::class)]
class JobQueueRepositoryTest extends TestCase
{
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
