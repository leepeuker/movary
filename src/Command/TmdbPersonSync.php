<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Command\Mapper\InputMapper;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Tmdb\SyncPersons;
use Movary\ValueObject\JobStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'tmdb:person:sync',
    description: 'Sync themoviedb.org meta data for local persons.',
    aliases: ['tmdb:person:sync'],
    hidden: false,
)]
class TmdbPersonSync extends Command
{
    private const string OPTION_NAME_FORCE_HOURS = 'hours';

    private const string OPTION_NAME_FORCE_THRESHOLD = 'threshold';

    private const string OPTION_NAME_PERSON_IDS = 'personIds';

    public function __construct(
        private readonly SyncPersons $syncPersons,
        private readonly JobQueueApi $jobQueueApi,
        private readonly InputMapper $inputMapper,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->addOption(self::OPTION_NAME_FORCE_THRESHOLD, 'threshold', InputOption::VALUE_REQUIRED, 'Max number of persons to sync.')
            ->addOption(self::OPTION_NAME_FORCE_HOURS, 'hours', InputOption::VALUE_REQUIRED, 'Hours since last updated.')
            ->addOption(self::OPTION_NAME_PERSON_IDS, 'personIds', InputOption::VALUE_REQUIRED, 'Comma seperated ids of persons to sync.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $maxAgeInHours = $this->inputMapper->mapOptionToInteger($input, self::OPTION_NAME_FORCE_HOURS);
        $maxSyncsThreshold = $this->inputMapper->mapOptionToInteger($input, self::OPTION_NAME_FORCE_THRESHOLD);
        $personIds = $this->inputMapper->mapOptionToIds($input, self::OPTION_NAME_PERSON_IDS);

        $jobId = $this->jobQueueApi->addTmdbPersonSyncJob(JobStatus::createInProgress());

        try {
            $this->generateOutput($output, 'Syncing person meta data...');

            $this->syncPersons->syncPersons($maxAgeInHours, $maxSyncsThreshold, $personIds);

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
