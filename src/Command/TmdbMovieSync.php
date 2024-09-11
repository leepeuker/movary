<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Command\Mapper\InputMapper;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Tmdb\SyncMovies;
use Movary\ValueObject\JobStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'tmdb:movie:sync',
    description: 'Sync themoviedb.org meta data for local movies.',
    aliases: ['tmdb:movie:sync'],
    hidden: false,
)]
class TmdbMovieSync extends Command
{
    private const string OPTION_NAME_FORCE_HOURS = 'hours';

    private const string OPTION_NAME_FORCE_THRESHOLD = 'threshold';

    private const string OPTION_NAME_MOVIE_IDS = 'movieIds';

    public function __construct(
        private readonly SyncMovies $syncMovieDetails,
        private readonly JobQueueApi $jobQueueApi,
        private readonly InputMapper $inputMapper,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->addOption(self::OPTION_NAME_FORCE_THRESHOLD, 'threshold', InputOption::VALUE_REQUIRED, 'Max number of movies to sync.')
            ->addOption(self::OPTION_NAME_FORCE_HOURS, 'hours', InputOption::VALUE_REQUIRED, 'Hours since last updated.')
            ->addOption(self::OPTION_NAME_MOVIE_IDS, 'movieIds', InputOption::VALUE_REQUIRED, 'Comma seperated ids of movies to sync.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $maxAgeInHours = $this->inputMapper->mapOptionToInteger($input, self::OPTION_NAME_FORCE_HOURS);
        $maxSyncsThreshold = $this->inputMapper->mapOptionToInteger($input, self::OPTION_NAME_FORCE_THRESHOLD);
        $movieIds = $this->inputMapper->mapOptionToIds($input, self::OPTION_NAME_MOVIE_IDS);

        $jobId = $this->jobQueueApi->addTmdbMovieSyncJob(JobStatus::createInProgress());

        try {
            $this->generateOutput($output, 'Syncing movie meta data...');

            $this->syncMovieDetails->syncMovies($maxAgeInHours, $maxSyncsThreshold, $movieIds);

            $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createDone());
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete tmdb sync.');
            $this->logger->error('Could not complete tmdb sync.', ['exception' => $t]);

            $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createFailed());

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Syncing movie meta data done.');

        return Command::SUCCESS;
    }
}
