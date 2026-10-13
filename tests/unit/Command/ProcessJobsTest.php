<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\ProcessJobs;
use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobScheduleProcessor;
use Movary\Service\JobProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(ProcessJobs::class)]
class ProcessJobsTest extends TestCase
{
    public function testSchedulesDueJobsBeforeFetchingQueue() : void
    {
        $jobScheduleProcessor = $this->createMock(JobScheduleProcessor::class);
        $jobScheduleProcessor->expects(self::once())->method('enqueueDueJobs');
        $jobQueueApi = $this->createMock(JobQueueApi::class);
        $jobQueueApi->expects(self::once())->method('fetchOldestWaitingJob')->willReturn(null);
        $tester = new CommandTester(new ProcessJobs(
            $jobQueueApi,
            $jobScheduleProcessor,
            $this->createStub(JobProcessor::class),
            $this->createStub(LoggerInterface::class),
            0,
        ));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }
}
