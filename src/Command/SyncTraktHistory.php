<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\Service\Trakt\SyncRatings;
use Movary\Application\Service\Trakt\SyncWatchedMovies;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncTraktHistory extends Command
{
    private const OPTION_NAME_HISTORY = 'history';

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
             ->addOption(self::OPTION_NAME_HISTORY, [], InputOption::VALUE_NONE, 'history')
             ->addOption(self::OPTION_NAME_RATINGS, [], InputOption::VALUE_NONE, 'ratings')
             ->addOption(self::OPTION_NAME_OVERWRITE, [], InputOption::VALUE_NONE, 'overwrite');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $overwriteExistingData = (bool)$input->getOption(self::OPTION_NAME_OVERWRITE);
        $syncRatings = (bool)$input->getOption(self::OPTION_NAME_RATINGS);
        $syncHistory = (bool)$input->getOption(self::OPTION_NAME_HISTORY);

        try {
            if ($syncRatings === false && $syncHistory === false) {
                $this->syncWatchedMovies->execute($overwriteExistingData);
                $this->syncWatchedMovies->execute($overwriteExistingData);
            } else {
                if ($syncRatings === true) {
                    $this->syncRatings->execute($overwriteExistingData);
                }
                if ($syncHistory === true) {
                    $this->syncWatchedMovies->execute($overwriteExistingData);
                }
            }
        } catch (\Throwable $t) {
            $this->logger->error('Could not complete trakt sync.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
