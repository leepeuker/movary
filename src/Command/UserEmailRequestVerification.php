<?php declare(strict_types=1);

namespace Movary\Command;

use Movary\Domain\User\Service\EmailVerificationRequestService;
use Movary\Domain\User\UserApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'user:email:request-verification',
    description: 'Schedule an email verification email for a user.',
    aliases: ['user:email:request-verification'],
    hidden: false,
)]
class UserEmailRequestVerification extends Command
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly EmailVerificationRequestService $requestService,
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

            if ($user->isEmailVerified() === true) {
                $this->generateOutput($output, 'Email address is already verified for this user.');

                return Command::FAILURE;
            }

            if ($this->requestService->requestForUser($user, true) === false) {
                $this->generateOutput($output, 'Could not schedule email verification. Check the logs for details.');

                return Command::FAILURE;
            }
        } catch (Throwable $throwable) {
            $this->logger->error('Could not schedule email verification.', ['exception' => $throwable]);
            $this->generateOutput($output, 'Could not schedule email verification.');

            return Command::FAILURE;
        }

        $this->generateOutput($output, 'Email verification queued. A jobs:process worker must process the job.');

        return Command::SUCCESS;
    }
}
