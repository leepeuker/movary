<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\JobQueue\JobQueueApi;
use Movary\Service\Tmdb\SyncPersons;
use Movary\ValueObject\JobStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class TmdbPersonSync extends Command
{
    private const OPTION_NAME_FORCE_HOURS = 'hours';

    private const OPTION_NAME_FORCE_THRESHOLD = 'threshold';

    protected static $defaultName = 'tmdb:person:sync';

    public function __construct(
        private readonly SyncPersons $syncPersons,
        private readonly JobQueueApi $jobQueueApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Sync themoviedb.org meta data for local persons.')
            ->addOption(self::OPTION_NAME_FORCE_THRESHOLD, 'threshold', InputOption::VALUE_REQUIRED, 'Max number of persons to sync.')
            ->addOption(self::OPTION_NAME_FORCE_HOURS, 'hours', InputOption::VALUE_REQUIRED, 'Hours since last updated.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $hoursOption = $input->getOption(self::OPTION_NAME_FORCE_HOURS);
        $maxAgeInHours = $hoursOption !== null ? (int)$hoursOption : null;

        $thresholdOption = $input->getOption(self::OPTION_NAME_FORCE_THRESHOLD);
        $personCountSyncThreshold = $thresholdOption !== null ? (int)$thresholdOption : null;

        $jobId = $this->jobQueueApi->addTmdbPersonSyncJob(JobStatus::createInProgress());

        try {
            $this->generateOutput($output, 'Syncing person meta data...');

            $this->syncPersons->syncPersons($maxAgeInHours, $personCountSyncThreshold);

            $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createDone());

            $this->generateOutput($output, 'Syncing person meta data done.');
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete tmdb person sync.');
            $this->logger->error('Could not complete tmdb person sync.', ['exception' => $t]);

            $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createFailed());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
