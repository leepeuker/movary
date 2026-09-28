<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\ApplicationUrlService;
use Movary\Service\Email\EmailSupport;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class PasswordResetRequestService
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly EmailSupport $emailSupport,
        private readonly ApplicationUrlService $applicationUrlService,
        private readonly JobQueueApi $jobQueueApi,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function request(string $email) : bool
    {
        return $this->schedule($email);
    }

    public function requestForUser(UserEntity $user) : bool
    {
        return $this->schedule($user->getEmail(), $user, true);
    }

    private function schedule(string $email, ?UserEntity $user = null, bool $ignoreCooldown = false) : bool
    {
        if ($this->emailSupport->isEnabled() === false) {
            $this->logger->info('Password reset email not scheduled because email support is disabled.');

            return false;
        }

        try {
            if ($this->applicationUrlService->hasApplicationUrl() === false) {
                throw new RuntimeException('APPLICATION_URL must be configured to send password reset emails.');
            }

            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return false;
            }

            $user ??= $this->userApi->findUserByEmail($email);
            if ($user === null || $user->hasCoreAccountChangesDisabled() === true) {
                $this->logger->debug('Password reset email not scheduled because email does not exist.', ['email' => $email]);

                return false;
            }

            $this->jobQueueApi->addPasswordResetEmailJob($user->getId(), $ignoreCooldown);
            $this->logger->info('Password reset email job scheduled.', ['userId' => $user->getId()]);

            return true;
        } catch (Throwable $exception) {
            $this->logger->error('Could not schedule password reset email.', ['exception' => $exception]);

            return false;
        }
    }
}
