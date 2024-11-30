<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Jellyfin\JellyfinMoviesExporter;
use Movary\ValueObject\JobStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'jellyfin:export',
    description: 'Export Movary watch dates as plays to Jellyfin.',
    aliases: ['jellyfin:export'],
    hidden: false,
)]
class JellyfinExport extends Command
{
    private const string OPTION_NAME_USER_ID = 'userId';

    public function __construct(
        private readonly JellyfinMoviesExporter $jellyfinMoviesExporter,
        private readonly JobQueueApi $jobQueueApi,
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->addArgument(self::OPTION_NAME_USER_ID, InputArgument::REQUIRED, 'Id of user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getArgument(self::OPTION_NAME_USER_ID);

        $jobId = $this->jobQueueApi->addJellyfinExportMoviesJob($userId, jobStatus: JobStatus::createInProgress());

        try {
            $this->generateOutput($output, 'Exporting movie watch dates to Jellyfin...');

            $this->jellyfinMoviesExporter->exportMoviesWatchStateToJellyfin(
                $userId,
                $this->movieHistoryApi->fetchMovieIdsWithWatchDatesByUserId($userId), false,
            );
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete Jellyfin export.');
            $this->logger->error('Could not complete Jellyfin export', ['exception' => $t]);

            $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createFailed());

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Exporting movie watch dates to Jellyfin done.');

        return Command::SUCCESS;
    }
}
