<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\Service\Trakt\SyncRatings;
use Movary\Application\Service\Trakt\SyncWatchedMovies;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncTrakt extends Command
{
    protected static $defaultName = 'app:sync-trakt';

    private LoggerInterface $logger;

    private SyncRatings $syncRatings;

    private SyncWatchedMovies $syncWatchedMovies;

    public function __construct(SyncRatings $syncRatings, SyncWatchedMovies $syncWatchedMovies, LoggerInterface $logger)
    {
        parent::__construct();

        $this->syncRatings = $syncRatings;
        $this->syncWatchedMovies = $syncWatchedMovies;
        $this->logger = $logger;
    }

    protected function configure() : void
    {
        $this->setDescription('Sync trakt.tv movie history and rating with local database');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $username = 'leepe';

        try {
            $this->syncWatchedMovies->execute($username);
            $this->syncRatings->execute();
        } catch (\Throwable $t) {
            $this->logger->error('Could not complete trakt sync.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
