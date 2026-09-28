<?php declare(strict_types=1);

namespace Movary\Service\Email;

use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\JobQueue\JobEntity;
use Movary\Service\ApplicationUrlService;
use Movary\ValueObject\RelativeUrl;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class PasswordResetEmailJobProcessor
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly EmailSupport $emailSupport,
        private readonly PasswordResetEmailRenderer $passwordResetEmailRenderer,
        private readonly PasswordResetTokenService $tokenService,
        private readonly ApplicationUrlService $applicationUrlService,
        private readonly SmtpConfigFactory $smtpConfigFactory,
        private readonly EmailService $emailService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $this->getUserId($job);
        $ignoreCooldown = $this->getIgnoreCooldown($job);
        $this->ensureEmailCanBeSent();
        $user = $this->getUser($userId);
        $smtpConfig = $this->smtpConfigFactory->create();
        $token = null;

        try {
            $token = $this->createToken($userId, $ignoreCooldown);
            if ($token === null) {
                $this->logger->info('Password reset email not sent because token creation is in cooldown.', ['userId' => $userId]);

                return;
            }

            $this->sendEmail($user, $token, $smtpConfig);
            $this->logger->info('Password reset email sent.', ['userId' => $userId]);
        } catch (Throwable $exception) {
            if ($token !== null) {
                $this->deleteTokenAfterFailure($token);
            }

            throw $exception;
        }
    }

    private function createToken(int $userId, bool $ignoreCooldown) : ?string
    {
        return $ignoreCooldown === true
            ? $this->tokenService->createToken($userId)
            : $this->tokenService->createTokenIfAllowed($userId);
    }

    private function deleteTokenAfterFailure(string $token) : void
    {
        try {
            $this->tokenService->deleteToken($token);
        } catch (Throwable $cleanupException) {
            $this->logger->error(
                'Could not remove password reset token after a failed job.',
                ['exception' => $cleanupException],
            );
        }
    }

    private function ensureEmailCanBeSent() : void
    {
        if ($this->emailSupport->isEnabled() === false) {
            throw new RuntimeException('Email support is disabled.');
        }
        if ($this->applicationUrlService->hasApplicationUrl() === false) {
            throw new RuntimeException('APPLICATION_URL must be configured to send password reset emails.');
        }
    }

    private function getIgnoreCooldown(JobEntity $job) : bool
    {
        $ignoreCooldown = $job->getParameters()['ignoreCooldown'] ?? false;
        if (is_bool($ignoreCooldown) === false) {
            throw new RuntimeException('Invalid parameter: ignoreCooldown');
        }

        return $ignoreCooldown;
    }

    private function getUser(int $userId) : UserEntity
    {
        $user = $this->userApi->findUserById($userId);
        if ($user === null || $user->hasCoreAccountChangesDisabled() === true) {
            throw new RuntimeException('Password resets are disabled for user: ' . $userId);
        }

        return $user;
    }

    private function getUserId(JobEntity $job) : int
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing parameter: userId');
        }

        return $userId;
    }

    private function sendEmail(UserEntity $user, string $token, SmtpConfig $smtpConfig) : void
    {
        $resetUrl = $this->applicationUrlService->createApplicationUrl(
            RelativeUrl::create('/reset-password?token=' . rawurlencode($token)),
        );
        $message = $this->passwordResetEmailRenderer->render(
            $resetUrl,
            PasswordResetTokenService::EXPIRATION_TIME_IN_MINUTES,
        );

        $this->emailService->sendEmail($user->getEmail(), 'Reset your Movary password', $message, $smtpConfig);
    }
}
