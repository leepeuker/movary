<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Application\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeUserPassword extends Command
{
    protected static $defaultName = self::COMMAND_BASE_NAME . ':user:change-password';

    public function __construct(
        private readonly User\Api $userApi,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this
            ->setDescription('Change user password.')
            ->addArgument('userId', InputArgument::REQUIRED, 'ID of user')
            ->addArgument('password', InputArgument::REQUIRED, 'New password for user');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getArgument('userId');
        $password = $input->getArgument('password');

        try {
            $this->userApi->updatePassword($userId, $password);
        } catch (User\Exception\PasswordTooShort $t) {
            $this->generateOutput($output, "Error: Password must be at least {$t->getMinLength()} characters long.");

            return Command::FAILURE;
        } catch (\Throwable $t) {
            $this->logger->error('Could not change password.', ['exception' => $t]);

            $this->generateOutput($output, 'Could not update password.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Updated password.');

        return Command::SUCCESS;
    }
}
