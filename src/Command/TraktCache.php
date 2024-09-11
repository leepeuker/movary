<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Service\Trakt\TraktCacheService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'trakt:updateCache',
    description: 'Update the local trakt cache timestamps with the remote state.',
    aliases: ['trakt:updateCache'],
    hidden: false,
)]
class TraktCache extends Command
{
    private const string OPTION_NAME_USER_ID = 'userId';

    public function __construct(
        private readonly TraktCacheService $traktCacheService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->addOption(self::OPTION_NAME_USER_ID, [], InputOption::VALUE_REQUIRED, 'Id of user.');
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
