<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\Service\Trakt\Sync;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncTrakt extends Command
{
    private const OPTION_NAME_OVERWRITE = 'overwrite';

    protected static $defaultName = 'app:sync-trakt';

    public function __construct(
        private readonly Sync $syncService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->setDescription('Sync trakt.tv movie history and rating with local database')
             ->addOption(self::OPTION_NAME_OVERWRITE, 'f', InputOption::VALUE_NONE, 'overwrite');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $overwriteExistingData = (bool)$input->getOption(self::OPTION_NAME_OVERWRITE);

        try {
            $this->syncService->syncAll($overwriteExistingData);
        } catch (\Throwable $t) {
            $this->logger->error('Could not complete trakt sync.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
