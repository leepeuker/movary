<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Command\Mapper\InputMapper;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Imdb\ImdbMovieRatingSync;
use Movary\ValueObject\JobStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class ImdbSync extends Command
{
    private const OPTION_NAME_FORCE_HOURS = 'hours';

    private const OPTION_NAME_FORCE_THRESHOLD = 'threshold';

    private const OPTION_NAME_MOVIE_IDS = 'movieIds';

    protected static $defaultName = 'imdb:sync';

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
            ->setDescription('Sync imdb ratings for local movies, sorted by how outdated they are (oldest first).')
            ->addOption(self::OPTION_NAME_MOVIE_IDS, 'movieIds', InputOption::VALUE_REQUIRED, 'Comma separated string of movie ids to force sync.')
            ->addOption(self::OPTION_NAME_FORCE_THRESHOLD, 'threshold', InputOption::VALUE_REQUIRED, 'Maximum number of movies to sync.')
            ->addOption(self::OPTION_NAME_FORCE_HOURS, 'hours', InputOption::VALUE_REQUIRED, 'Number of hours required to have elapsed since last sync.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $movieCountSyncThreshold = $this->inputMapper->mapOptionToInteger($input, self::OPTION_NAME_FORCE_THRESHOLD);
        $maxAgeInHours = $this->inputMapper->mapOptionToInteger($input, self::OPTION_NAME_FORCE_HOURS);
        $movieIds = $this->inputMapper->mapOptionToIds($input, self::OPTION_NAME_MOVIE_IDS);

        $jobId = $this->jobQueueApi->addImdbSyncJob(JobStatus::createInProgress());

        try {
            $this->generateOutput($output, 'Syncing imdb movie ratings...');

            $this->imdbMovieRatingSync->syncMultipleMovieRatings($maxAgeInHours, $movieCountSyncThreshold, $movieIds);

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
