<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\Service\Letterboxd\SyncRatings;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncLetterboxd extends Command
{
    protected static $defaultName = 'app:sync-letterboxd';

    private LoggerInterface $logger;

    private SyncRatings $syncRatings;

    public function __construct(SyncRatings $syncRatings, LoggerInterface $logger)
    {
        parent::__construct();

        $this->syncRatings = $syncRatings;
        $this->logger = $logger;
    }

    protected function configure() : void
    {
        $this->setDescription('Sync letterboxd.com movie history and rating with local database');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {
            $this->syncRatings->execute();
        } catch (\Throwable $t) {
            $this->logger->error('Could not complete letterboxd sync.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
