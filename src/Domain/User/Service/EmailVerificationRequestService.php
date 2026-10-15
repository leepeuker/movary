<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Email\EmailSupport;
use Psr\Log\LoggerInterface;
use Throwable;

class EmailVerificationRequestService
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly EmailSupport $emailSupport,
        private readonly JobQueueApi $jobQueueApi,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function request(string $email) : bool
    {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $user = $this->userApi->findUserByEmail($email) ?? $this->userApi->findUserByPendingEmail($email);

        return $this->schedule($user);
    }

    public function requestForUser(UserEntity $user, bool $ignoreCooldown = false) : bool
    {
        return $this->schedule($user, $ignoreCooldown);
    }

    private function schedule(?UserEntity $user, bool $ignoreCooldown = false) : bool
    {
        if ($this->emailSupport->isEmailVerificationAvailable() === false) {
            $this->logger->info('Email verification not scheduled because email support is unavailable.');

            return false;
        }

        if ($user === null || $user->getEmailPendingVerification() === null) {
            $this->logger->debug('Email verification not scheduled because the account does not require it.');

            return false;
        }

        try {
            $this->jobQueueApi->addEmailVerificationJob($user->getId(), $ignoreCooldown);
            $this->logger->info('Email verification job scheduled.', ['userId' => $user->getId()]);

            return true;
        } catch (Throwable $exception) {
            $this->logger->error('Could not schedule email verification.', ['exception' => $exception]);

            return false;
        }
    }
}
