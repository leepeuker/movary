<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeUserTraktUserName extends Command
{
    protected static $defaultName = self::COMMAND_BASE_NAME . ':user:change-trakt-username';

    public function __construct(
        private readonly User\Api $userApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Change user trakt client id.')
            ->addArgument('userId', InputArgument::REQUIRED, 'ID of user')
            ->addArgument('traktUserName', InputArgument::REQUIRED, 'New trakt username for user');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getArgument('userId');
        $traktUserName = $input->getArgument('traktUserName');

        if (empty($traktUserName) === true) {
            $traktUserName = null;
        }

        try {
            $this->userApi->updateTraktUserName($userId, $traktUserName);
        } catch (\Throwable $t) {
            $this->logger->error('Could not change trakt username.', ['exception' => $t]);

            $this->generateOutput($output, 'Could not update trakt username.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Updated trakt username.');
        return Command::SUCCESS;
    }
}
