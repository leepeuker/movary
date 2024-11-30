<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\JobQueue\JobQueueApi;
use Movary\Service\Plex\PlexWatchlistImporter;
use Movary\ValueObject\JobStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'plex:watchlist:import',
    description: 'Import missing movies from Plex watchlist to the Movary watchlist.',
    aliases: ['plex:watchlist:import'],
    hidden: false,
)]
class PlexWatchlistImport extends Command
{
    private const string OPTION_NAME_USER_ID = 'userId';

    public function __construct(
        private readonly PlexWatchlistImporter $plexWatchlistImporter,
        private readonly JobQueueApi $jobQueueApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->addOption(self::OPTION_NAME_USER_ID, [], InputOption::VALUE_REQUIRED, 'Id of user to import for.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getOption(self::OPTION_NAME_USER_ID);
        if (empty($userId) === true) {
            $this->generateOutput($output, 'Missing option --userId');
            exit;
        }

        $jobId = $this->jobQueueApi->addPlexImportWatchlistJob($userId, JobStatus::createInProgress());

        try {
            $this->plexWatchlistImporter->importPlexWatchlist($userId);

            $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createDone());
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete Plex watchlist import.');
            $this->logger->error('Could not complete Plex watchlist import.', ['exception' => $t]);

            $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createFailed());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
