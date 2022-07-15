<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\User\Api;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserDelete extends Command
{
    protected static $defaultName = 'user:delete';

    public function __construct(
        private readonly Api $userApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Delete a user.')
            ->addArgument('userId', InputArgument::REQUIRED, 'Id of user to delete.');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        try {
            $this->userApi->deleteUser((int)$input->getArgument('userId'));
        } catch (\Throwable $t) {
            $this->logger->error('Could not delete user.', ['exception' => $t]);

            $this->generateOutput($output, 'Could not delete user.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'User deleted.');

        return Command::SUCCESS;
    }
}
