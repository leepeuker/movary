<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Command\Mapper\InputMapper;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Imdb\ImdbMovieRatingSync;
use Movary\ValueObject\JobStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'imdb:sync',
    description: 'Sync imdb ratings for local movies, sorted by how outdated they are (oldest first).',
    aliases: ['imdb:sync'],
    hidden: false,
)]
class ImdbSync extends Command
{
    private const string OPTION_NAME_NEVER_SYNC = 'never-synced';

    private const string OPTION_NAME_HOURS = 'hours';

    private const string OPTION_NAME_FORCE_THRESHOLD = 'threshold';

    private const string OPTION_NAME_MOVIE_IDS = 'movieIds';

    public function __construct(
        private readonly ImdbMovieRatingSync $imdbMovieRatingSync,
        private readonly JobQueueApi $jobQueueApi,
        private readonly InputMapper $inputMapper,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->addOption(self::OPTION_NAME_MOVIE_IDS, 'movieIds', InputOption::VALUE_REQUIRED, 'Comma separated string of movie ids to force sync.')
            ->addOption(self::OPTION_NAME_FORCE_THRESHOLD, 'threshold', InputOption::VALUE_REQUIRED, 'Maximum number of movies to sync.')
            ->addOption(self::OPTION_NAME_HOURS, 'hours', InputOption::VALUE_REQUIRED, 'Number of hours required to have elapsed since last sync.')
            ->addOption(self::OPTION_NAME_NEVER_SYNC, 'never-synced', InputOption::VALUE_NONE, 'Only sync ratings for movies which where never synced before.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $movieCountSyncThreshold = $this->inputMapper->mapOptionToInteger($input, self::OPTION_NAME_FORCE_THRESHOLD);
        $maxAgeInHours = $this->inputMapper->mapOptionToInteger($input, self::OPTION_NAME_HOURS);
        $movieIds = $this->inputMapper->mapOptionToIds($input, self::OPTION_NAME_MOVIE_IDS);
        $onlyNeverSynced = (bool)$input->getOption(self::OPTION_NAME_NEVER_SYNC);

        $jobId = $this->jobQueueApi->addImdbSyncJob(JobStatus::createInProgress());

        try {
            $this->generateOutput($output, 'Syncing imdb movie ratings...');

            $this->imdbMovieRatingSync->syncMultipleMovieRatings($maxAgeInHours, $movieCountSyncThreshold, $movieIds, onlyNeverSynced: $onlyNeverSynced);

            $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createDone());

            $this->generateOutput($output, 'Syncing imdb movie ratings done.');
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete imdb sync.');
            $this->logger->error('Could not complete imdb sync.', ['exception' => $t]);

            $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createFailed());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
