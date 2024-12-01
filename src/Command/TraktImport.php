<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\JobQueue\JobQueueApi;
use Movary\Service\Trakt\Exception\TraktClientIdNotSet;
use Movary\Service\Trakt\Exception\TraktUsernameNotSet;
use Movary\Service\Trakt\ImportRatings;
use Movary\Service\Trakt\ImportWatchedMovies;
use Movary\ValueObject\JobStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'trakt:import',
    description: 'Import trakt.tv movie history and rating with local database.',
    aliases: ['trakt:import'],
    hidden: false,
)]
class TraktImport extends Command
{
    private const string OPTION_NAME_HISTORY = 'history';

    private const string OPTION_NAME_IGNORE_CACHE = 'ignore-cache';

    private const string OPTION_NAME_OVERWRITE = 'overwrite';

    private const string OPTION_NAME_RATINGS = 'ratings';

    private const string OPTION_NAME_USER_ID = 'userId';

    public function __construct(
        private readonly ImportRatings $importRatings,
        private readonly ImportWatchedMovies $importWatchedMovies,
        private readonly JobQueueApi $jobQueueApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->addOption(self::OPTION_NAME_USER_ID, [], InputOption::VALUE_REQUIRED, 'Id of user to import to.')
            ->addOption(self::OPTION_NAME_HISTORY, [], InputOption::VALUE_NONE, 'Import movie history.')
            ->addOption(self::OPTION_NAME_RATINGS, [], InputOption::VALUE_NONE, 'Import movie ratings.')
            ->addOption(self::OPTION_NAME_OVERWRITE, [], InputOption::VALUE_NONE, 'Overwrite local data.')
            ->addOption(self::OPTION_NAME_IGNORE_CACHE, [], InputOption::VALUE_NONE, 'Ignore trakt cache and force import everything.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getOption(self::OPTION_NAME_USER_ID);
        if (empty($userId) === true) {
            $this->generateOutput($output, 'Missing option --userId');
            exit;
        }

        $overwriteExistingData = (bool)$input->getOption(self::OPTION_NAME_OVERWRITE);
        $ignoreCache = (bool)$input->getOption(self::OPTION_NAME_IGNORE_CACHE);

        $importRatings = (bool)$input->getOption(self::OPTION_NAME_RATINGS);
        $importHistory = (bool)$input->getOption(self::OPTION_NAME_HISTORY);

        try {
            if ($importRatings === false && $importHistory === false) {
                $this->importHistory($output, $userId, $overwriteExistingData, $ignoreCache);
                $this->importRatings($output, $userId, $overwriteExistingData);
            } else {
                if ($importHistory === true) {
                    $this->importHistory($output, $userId, $overwriteExistingData, $ignoreCache);
                }
                if ($importRatings === true) {
                    $this->importRatings($output, $userId, $overwriteExistingData);
                }
            }
        } catch (TraktClientIdNotSet $t) {
            $this->generateOutput($output, 'ERROR: User as no trakt client id set.');

            return Command::FAILURE;
        } catch (TraktUsernameNotSet $t) {
            $this->generateOutput($output, 'ERROR: User as no trakt user name set.');

            return Command::FAILURE;
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete trakt import.');
            $this->logger->error('Could not complete trakt import.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function importHistory(OutputInterface $output, int $userId, bool $overwriteExistingData, bool $ignoreCache) : void
    {
        $this->generateOutput($output, 'Importing history...');

        $jobId = $this->jobQueueApi->addTraktImportHistoryJob($userId, JobStatus::createInProgress());

        $this->importWatchedMovies->execute($userId, $overwriteExistingData, $ignoreCache);

        $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createDone());

        $this->generateOutput($output, 'Importing history done.');
    }

    private function importRatings(OutputInterface $output, int $userId, bool $overwriteExistingData) : void
    {
        $this->generateOutput($output, 'Importing ratings...');

        $jobId = $this->jobQueueApi->addTraktImportRatingsJob($userId, JobStatus::createInProgress());

        $this->importRatings->execute($userId, $overwriteExistingData);

        $this->jobQueueApi->updateJobStatus($jobId, JobStatus::createDone());

        $this->generateOutput($output, 'Importing ratings ratings.');
    }
}
