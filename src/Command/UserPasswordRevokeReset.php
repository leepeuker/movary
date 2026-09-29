<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\Domain\User\UserApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'user:password:revoke-reset',
    description: 'Revoke a pending password reset for a user.',
    aliases: ['user:password:revoke-reset'],
    hidden: false,
)]
class UserPasswordRevokeReset extends Command
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly PasswordResetTokenService $tokenService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure() : void
    {
        $this->addArgument('userId', InputArgument::REQUIRED, 'ID of user');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $userId = (int)$input->getArgument('userId');

        try {
            if ($this->userApi->findUserById($userId) === null) {
                $this->generateOutput($output, 'User id does not exist: ' . $userId);

                return Command::FAILURE;
            }

            $this->tokenService->deleteTokenForUser($userId);
        } catch (Throwable $throwable) {
            $this->logger->error('Could not revoke password reset.', ['exception' => $throwable]);
            $this->generateOutput($output, 'Could not revoke password reset.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Password reset revoked.');

        return Command::SUCCESS;
    }
}
