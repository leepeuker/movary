<?php declare(strict_types=1);

namespace Movary\Command;

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

    protected static $defaultName = 'app:sync-trakt';

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
             ->addOption(self::OPTION_NAME_HISTORY, [], InputOption::VALUE_NONE, 'Sync movie history.')
             ->addOption(self::OPTION_NAME_RATINGS, [], InputOption::VALUE_NONE, 'Sync movie ratings.')
             ->addOption(self::OPTION_NAME_OVERWRITE, [], InputOption::VALUE_NONE, 'Overwrite local data.')
             ->addOption(self::OPTION_NAME_IGNORE_CACHE, [], InputOption::VALUE_NONE, 'Ignore trakt cache and force sync for everything.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $overwriteExistingData = (bool)$input->getOption(self::OPTION_NAME_OVERWRITE);
        $ignoreCache = (bool)$input->getOption(self::OPTION_NAME_IGNORE_CACHE);

        $syncRatings = (bool)$input->getOption(self::OPTION_NAME_RATINGS);
        $syncHistory = (bool)$input->getOption(self::OPTION_NAME_HISTORY);

        try {
            if ($syncRatings === false && $syncHistory === false) {
                $this->syncHistory($output, $overwriteExistingData, $ignoreCache);
                $this->syncRatings($output, $overwriteExistingData);
            } else {
                if ($syncHistory === true) {
                    $this->syncHistory($output, $overwriteExistingData, $ignoreCache);
                }
                if ($syncRatings === true) {
                    $this->syncRatings($output, $overwriteExistingData);
                }
            }
        } catch (\Throwable $t) {
            $this->logger->error('Could not complete trakt sync.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function syncHistory(OutputInterface $output, bool $overwriteExistingData, bool $ignoreCache) : void
    {
        $this->generateOutput($output, 'Syncing history...');

        $this->syncWatchedMovies->execute($overwriteExistingData, $ignoreCache);

        $this->generateOutput($output, 'Syncing history done.');
    }

    private function syncRatings(OutputInterface $output, bool $overwriteExistingData) : void
    {
        $this->generateOutput($output, 'Syncing ratings...');

        $this->syncRatings->execute($overwriteExistingData);

        $this->generateOutput($output, 'Syncing ratings ratings.');
    }
}
