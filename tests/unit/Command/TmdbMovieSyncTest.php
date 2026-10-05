<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\Mapper\InputMapper;
use Movary\Command\TmdbMovieSync;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Tmdb\SyncMovies;
use Movary\ValueObject\JobStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(TmdbMovieSync::class)]
class TmdbMovieSyncTest extends TestCase
{
    public function testNeverSyncedOptionIsPassedToSyncService() : void
    {
        $syncMovies = $this->createMock(SyncMovies::class);
        $syncMovies
            ->expects(self::once())
            ->method('syncMovies')
            ->with(null, null, null, true);

        $jobQueueApi = $this->createMock(JobQueueApi::class);
        $jobQueueApi
            ->expects(self::once())
            ->method('addTmdbMovieSyncJob')
            ->with(self::callback(static fn (JobStatus $status) : bool => (string)$status === 'in progress'))
            ->willReturn(1);
        $jobQueueApi->expects(self::once())->method('updateJobStatus');

        $tester = new CommandTester(new TmdbMovieSync(
            $syncMovies,
            $jobQueueApi,
            new InputMapper(),
            $this->createStub(LoggerInterface::class),
        ));

        self::assertSame(Command::SUCCESS, $tester->execute(['--never-synced' => true]));
    }
}
