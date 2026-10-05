<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\ImdbSync;
use Movary\Command\Mapper\InputMapper;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Imdb\ImdbMovieRatingSync;
use Movary\ValueObject\JobStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(ImdbSync::class)]
class ImdbSyncTest extends TestCase
{
    public function testMovieIdsOptionIsPassedToSyncServiceAndJob() : void
    {
        $sync = $this->createMock(ImdbMovieRatingSync::class);
        $sync
            ->expects(self::once())
            ->method('syncMultipleMovieRatings')
            ->with(null, null, [7, 8], false);

        $jobQueueApi = $this->createMock(JobQueueApi::class);
        $jobQueueApi
            ->expects(self::once())
            ->method('addImdbSyncJob')
            ->with(
                self::callback(static fn (JobStatus $status) : bool => (string)$status === 'in progress'),
                [7, 8],
            )
            ->willReturn(1);
        $jobQueueApi->expects(self::once())->method('updateJobStatus');

        $tester = new CommandTester(new ImdbSync(
            $sync,
            $jobQueueApi,
            new InputMapper(),
            $this->createStub(LoggerInterface::class),
        ));

        self::assertSame(Command::SUCCESS, $tester->execute(['--movieIds' => '7,8']));
    }
}
