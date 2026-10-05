<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\Mapper\InputMapper;
use Movary\Command\TmdbPersonSync;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Tmdb\SyncPersons;
use Movary\ValueObject\JobStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(TmdbPersonSync::class)]
class TmdbPersonSyncTest extends TestCase
{
    public function testNeverSyncedOptionIsPassedToSyncService() : void
    {
        $syncPersons = $this->createMock(SyncPersons::class);
        $syncPersons
            ->expects(self::once())
            ->method('syncPersons')
            ->with(null, null, null, true);

        $jobQueueApi = $this->createMock(JobQueueApi::class);
        $jobQueueApi
            ->expects(self::once())
            ->method('addTmdbPersonSyncJob')
            ->with(self::callback(static fn (JobStatus $status) : bool => (string)$status === 'in progress'))
            ->willReturn(1);
        $jobQueueApi->expects(self::once())->method('updateJobStatus');

        $tester = new CommandTester(new TmdbPersonSync(
            $syncPersons,
            $jobQueueApi,
            new InputMapper(),
            $this->createStub(LoggerInterface::class),
        ));

        self::assertSame(Command::SUCCESS, $tester->execute(['--never-synced' => true]));
    }
}
