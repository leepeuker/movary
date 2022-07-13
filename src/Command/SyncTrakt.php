<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\Service\Trakt\Exception\TraktClientIdNotSet;
use Movary\Application\Service\Trakt\Exception\TraktUserNameNotSet;
use Movary\Application\Service\Trakt\SyncRatings;
use Movary\Application\Service\Trakt\SyncWatchedMovies;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncTrakt extends Command
{
    private const OPTION_NAME_HISTORY = 'history';

    private const OPTION_NAME_IGNORE_CACHE = 'ignore-cache';

    private const OPTION_NAME_OVERWRITE = 'overwrite';

    private const OPTION_NAME_RATINGS = 'ratings';

    private const OPTION_NAME_USER_ID = 'userId';

    protected static $defaultName = self::COMMAND_BASE_NAME . ':sync-trakt';

    public function __construct(
        private readonly SyncRatings $syncRatings,
        private readonly SyncWatchedMovies $syncWatchedMovies,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->setDescription('Sync trakt.tv movie history and rating with local database')
             ->addOption(self::OPTION_NAME_USER_ID, [], InputOption::VALUE_REQUIRED, 'Id of user to sync to.')
             ->addOption(self::OPTION_NAME_HISTORY, [], InputOption::VALUE_NONE, 'Sync movie history.')
             ->addOption(self::OPTION_NAME_RATINGS, [], InputOption::VALUE_NONE, 'Sync movie ratings.')
             ->addOption(self::OPTION_NAME_OVERWRITE, [], InputOption::VALUE_NONE, 'Overwrite local data.')
             ->addOption(self::OPTION_NAME_IGNORE_CACHE, [], InputOption::VALUE_NONE, 'Ignore trakt cache and force sync for everything.');
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

        $syncRatings = (bool)$input->getOption(self::OPTION_NAME_RATINGS);
        $syncHistory = (bool)$input->getOption(self::OPTION_NAME_HISTORY);

        try {
            if ($syncRatings === false && $syncHistory === false) {
                $this->syncHistory($output, $userId, $overwriteExistingData, $ignoreCache);
                $this->syncRatings($output, $userId, $overwriteExistingData);
            } else {
                if ($syncHistory === true) {
                    $this->syncHistory($output, $userId, $overwriteExistingData, $ignoreCache);
                }
                if ($syncRatings === true) {
                    $this->syncRatings($output, $userId, $overwriteExistingData);
                }
            }
        } catch (TraktClientIdNotSet $t) {
            $this->generateOutput($output, 'ERROR: User as no trakt client id set.');

            return Command::FAILURE;
        } catch (TraktUserNameNotSet $t) {
            $this->generateOutput($output, 'ERROR: User as no trakt user name set.');

            return Command::FAILURE;
        } catch (\Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete trakt sync.');
            $this->logger->error('Could not complete trakt sync.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function syncHistory(OutputInterface $output, int $userId, bool $overwriteExistingData, bool $ignoreCache) : void
    {
        $this->generateOutput($output, 'Syncing history...');

        $this->syncWatchedMovies->execute($userId, $overwriteExistingData, $ignoreCache);

        $this->generateOutput($output, 'Syncing history done.');
    }

    private function syncRatings(OutputInterface $output, int $userId, bool $overwriteExistingData) : void
    {
        $this->generateOutput($output, 'Syncing ratings...');

        $this->syncRatings->execute($userId, $overwriteExistingData);

        $this->generateOutput($output, 'Syncing ratings ratings.');
    }
}
