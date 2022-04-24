<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\Service\Tmdb\SyncMovieDetails;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncTmdb extends Command
{
    private const OPTION_NAME_FORCE_SYNC = 'forceSync';

    protected static $defaultName = 'app:sync-tmdb';

    public function __construct(
        private readonly SyncMovieDetails $syncMovieDetails,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Sync trakt.tv movie history and rating with local database')
            ->addOption(self::OPTION_NAME_FORCE_SYNC, 'f', InputOption::VALUE_NEGATABLE, 'Force the sync', false);
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $forceSync = (bool)$input->getOption(self::OPTION_NAME_FORCE_SYNC);

        try {
            $this->syncMovieDetails->execute($forceSync);
        } catch (\Throwable $t) {
            $this->logger->error('Could not complete tmdb sync.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
