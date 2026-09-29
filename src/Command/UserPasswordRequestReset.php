<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Domain\User\Service\PasswordResetRequestService;
use Movary\Domain\User\UserApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'user:password:request-reset',
    description: 'Schedule a password reset email for a user.',
    aliases: ['user:password:request-reset'],
    hidden: false,
)]
class UserPasswordRequestReset extends Command
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly PasswordResetRequestService $requestService,
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
            $user = $this->userApi->findUserById($userId);
            if ($user === null) {
                $this->generateOutput($output, 'User id does not exist: ' . $userId);

                return Command::FAILURE;
            }

            if ($user->hasCoreAccountChangesDisabled() === true) {
                $this->generateOutput($output, 'Password resets are disabled for this user.');

                return Command::FAILURE;
            }

            if ($this->requestService->requestForUser($user) === false) {
                $this->generateOutput($output, 'Could not schedule password reset email. Check the logs for details.');

                return Command::FAILURE;
            }
        } catch (Throwable $throwable) {
            $this->logger->error('Could not schedule password reset email.', ['exception' => $throwable]);
            $this->generateOutput($output, 'Could not schedule password reset email.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Password reset email queued. A jobs:process worker must process the job.');

        return Command::SUCCESS;
    }
}
