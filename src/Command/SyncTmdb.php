<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\Service\Tmdb\SyncMovieDetails;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncTmdb extends Command
{
    /**
     * @var string|null
     */
    protected static $defaultName = 'app:sync-tmdb';

    private LoggerInterface $logger;

    private SyncMovieDetails $syncMovieDetails;

    public function __construct(SyncMovieDetails $syncMovieDetails, LoggerInterface $logger)
    {
        parent::__construct();

        $this->syncMovieDetails = $syncMovieDetails;
        $this->logger = $logger;
    }

    protected function configure() : void
    {
        $this->setDescription('Sync trakt.tv movie history and rating with local database');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->syncMovieDetails->execute();


        try {
        } catch (\Throwable $t) {
            $this->logger->error('Could not complete tmdb sync.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
