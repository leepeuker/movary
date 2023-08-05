<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Jellyfin\JellyfinMoviesExporter;
use Movary\Service\Jellyfin\JellyfinMoviesImporter;
use Movary\ValueObject\JobStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class JellyfinImport extends Command
{
    private const OPTION_NAME_USER_ID = 'userId';

    protected static $defaultName = 'jellyfin:import';

    public function __construct(
        private readonly JellyfinMoviesImporter $jellyfinMoviesImporter,
        private readonly JobQueueApi $jobQueueApi,
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->setDescription('Import Movary watch dates as plays to Jellyfin.')
            ->addArgument(self::OPTION_NAME_USER_ID, InputArgument::REQUIRED, 'Id of user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getArgument(self::OPTION_NAME_USER_ID);

        $jobId = $this->jobQueueApi->addJellyfinImportMoviesJob($userId, jobStatus: JobStatus::createInProgress());

        try {
            $this->generateOutput($output, 'Importing movie watch dates to Jellyfin...');

            $this->jellyfinMoviesImporter->importMoviesToJellyfin($userId);
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete Jellyfin import.');
            $this->logger->error('Could not complete Jellyfin import', ['exception' => $t]);

            $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createFailed());

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Importing movie watch dates to Jellyfin done.');

        return Command::SUCCESS;
    }
}
