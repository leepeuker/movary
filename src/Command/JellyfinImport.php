<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\JobQueue\JobQueueApi;
use Movary\Service\Jellyfin\JellyfinMoviesImporter;
use Movary\ValueObject\JobStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'jellyfin:import',
    description: 'Import Movary watch dates as plays from Jellyfin.',
    aliases: ['jellyfin:import'],
    hidden: false,
)]
class JellyfinImport extends Command
{
    private const string OPTION_NAME_USER_ID = 'userId';

    public function __construct(
        private readonly JellyfinMoviesImporter $jellyfinMoviesImporter,
        private readonly JobQueueApi $jobQueueApi,
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

        $jobId = $this->jobQueueApi->addJellyfinImportMoviesJob($userId, jobStatus: JobStatus::createInProgress());

        try {
            $this->generateOutput($output, 'Importing movie plays from Jellyfin...');

            $this->jellyfinMoviesImporter->importMoviesFromJellyfin($userId);
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete Jellyfin import.');
            $this->logger->error('Could not complete Jellyfin import', ['exception' => $t]);

            $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createFailed());

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Importing movie plays from Jellyfin done.');

        return Command::SUCCESS;
    }
}
