<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\Service\Imdb\SyncMovies;
use Movary\ValueObject\JobStatus;
use Movary\Worker\Service;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImdbSync extends Command
{
    private const OPTION_NAME_FORCE_HOURS = 'hours';

    private const OPTION_NAME_FORCE_THRESHOLD = 'threshold';

    protected static $defaultName = 'imdb:sync';

    public function __construct(
        private readonly SyncMovies $syncMovieDetails,
        private readonly Service $workerService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Sync imdb ratings for local movies.')
            ->addOption(self::OPTION_NAME_FORCE_THRESHOLD, 'threshold', InputOption::VALUE_REQUIRED, 'Max number of movies to sync.')
            ->addOption(self::OPTION_NAME_FORCE_HOURS, 'hours', InputOption::VALUE_REQUIRED, 'Hours since last updated.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $hoursOption = $input->getOption(self::OPTION_NAME_FORCE_HOURS);
        $maxAgeInHours = $hoursOption !== null ? (int)$hoursOption : null;

        $thresholdOption = $input->getOption(self::OPTION_NAME_FORCE_THRESHOLD);
        $movieCountSyncThreshold = $thresholdOption !== null ? (int)$thresholdOption : null;

        try {
            $this->generateOutput($output, 'Syncing imdb movie ratings...');

            $this->syncMovieDetails->syncMovies($maxAgeInHours, $movieCountSyncThreshold);

            $this->workerService->addImdbSyncJob(JobStatus::createDone());

            $this->generateOutput($output, 'Syncing imdb movie ratings done.');
        } catch (\Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete imdb sync.');
            $this->logger->error('Could not complete imdb sync.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
