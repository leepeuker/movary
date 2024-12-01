<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Api\Jellyfin\Cache;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'jellyfin:cache:delete',
    description: 'Delete the local cache of Jellyfin movies.',
    aliases: ['jellyfin:cache:delete'],
    hidden: false,
)]
class JellyfinCacheDelete extends Command
{
    private const string OPTION_NAME_USER_ID = 'userId';

    public function __construct(
        private readonly Cache\JellyfinCache $jellyfinCache,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->addArgument(self::OPTION_NAME_USER_ID, InputArgument::REQUIRED, 'Id of user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getArgument(self::OPTION_NAME_USER_ID);

        try {
            $this->jellyfinCache->delete($userId);
        } catch (Throwable $t) {
            $this->generateOutput($output, 'ERROR: Could not complete Jellyfin cache deletion.');
            $this->logger->error('Could not complete Jellyfin cache deletion.', ['exception' => $t]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
