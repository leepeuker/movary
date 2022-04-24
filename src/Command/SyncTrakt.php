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

    public function __construct(
        private readonly SyncRatings $syncRatings,
        private readonly SyncWatchedMovies $syncWatchedMovies,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->setDescription('Sync trakt.tv movie history and rating with local database');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {
            $this->syncWatchedMovies->execute();
            $this->syncRatings->execute();
        } catch (\Throwable $t) {
            $this->logger->error('Could not complete trakt sync.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
