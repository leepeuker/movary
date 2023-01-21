<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Service\Trakt\ImportRatings;
use Movary\Service\Trakt\ImportWatchedMovies;
use Movary\Service\Trakt\TraktCacheService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class TraktCache extends Command
{
    private const OPTION_NAME_USER_ID = 'userId';

    protected static $defaultName = 'trakt:updateCache';

    public function __construct(
        private readonly ImportRatings $importRatings,
        private readonly ImportWatchedMovies $importWatchedMovies,
        private readonly TraktCacheService $traktCacheService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->setDescription('Update the local trakt cache timestamps with the remote state.')
            ->addOption(self::OPTION_NAME_USER_ID, [], InputOption::VALUE_REQUIRED, 'Id of user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getOption(self::OPTION_NAME_USER_ID);
        if (empty($userId) === true) {
            $this->generateOutput($output, 'Missing option --userId');
            exit;
        }

        try {
            $this->traktCacheService->updateCache($userId);
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete trakt cache update.');
            $this->logger->error('Could not complete trakt cache update.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
