<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\Service\Tmdb\SyncMovieCredits;
use Movary\Application\Service\Tmdb\SyncMovieDetails;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncTmdb extends Command
{
    protected static $defaultName = 'app:sync-tmdb';

    private LoggerInterface $logger;

    private SyncMovieCredits $syncMovieCredits;

    private SyncMovieDetails $syncMovieDetails;

    public function __construct(SyncMovieDetails $syncMovieDetails, SyncMovieCredits $syncMovieCredits, LoggerInterface $logger)
    {
        parent::__construct();

        $this->syncMovieDetails = $syncMovieDetails;
        $this->syncMovieCredits = $syncMovieCredits;
        $this->logger = $logger;
    }

    protected function configure() : void
    {
        $this->setDescription('Sync trakt.tv movie history and rating with local database');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {
            $this->syncMovieDetails->execute();
            $this->syncMovieCredits->execute();
        } catch (\Throwable $t) {
            $this->logger->error('Could not complete tmdb sync.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
