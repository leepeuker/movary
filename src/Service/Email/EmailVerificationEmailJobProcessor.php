<?php declare(strict_types=1);

namespace Movary\Service\Email;

use Movary\Domain\User\Service\EmailVerificationTokenService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\JobQueue\JobEntity;
use Movary\Service\ApplicationUrlService;
use Movary\ValueObject\RelativeUrl;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class EmailVerificationEmailJobProcessor
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly EmailSupport $emailSupport,
        private readonly EmailVerificationEmailRenderer $emailRenderer,
        private readonly EmailVerificationTokenService $tokenService,
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
        $email = $user->getEmailPendingVerification();
        if ($email === null) {
            $this->logger->info('Email verification not sent because the address is already verified.', ['userId' => $userId]);

            return;
        }

        $smtpConfig = $this->smtpConfigFactory->create();
        $token = null;

        try {
            $token = $ignoreCooldown === true
                ? $this->tokenService->createToken($userId, $email)
                : $this->tokenService->createTokenIfAllowed($userId, $email);
            if ($token === null) {
                $this->logger->info('Email verification not sent because token creation is in cooldown.', ['userId' => $userId]);

                return;
            }

            $this->sendEmail($email, $token, $smtpConfig);
            $this->logger->info('Email verification sent.', ['userId' => $userId]);
        } catch (Throwable $exception) {
            if ($token !== null) {
                $this->deleteTokenAfterFailure($token);
            }

            throw $exception;
        }
    }

    private function deleteTokenAfterFailure(string $token) : void
    {
        try {
            $this->tokenService->deleteToken($token);
        } catch (Throwable $cleanupException) {
            $this->logger->error(
                'Could not remove email verification token after a failed job.',
                ['exception' => $cleanupException],
            );
        }
    }

    private function ensureEmailCanBeSent() : void
    {
        if ($this->emailSupport->isEmailVerificationAvailable() === false) {
            throw new RuntimeException('Email verification is unavailable.');
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
        if ($user === null) {
            throw new RuntimeException('User does not exist: ' . $userId);
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

    private function sendEmail(string $email, string $token, SmtpConfig $smtpConfig) : void
    {
        $verificationUrl = $this->applicationUrlService->createApplicationUrl(
            RelativeUrl::create('/verify-email?token=' . rawurlencode($token)),
        );
        $message = $this->emailRenderer->render(
            $verificationUrl,
            EmailVerificationTokenService::EXPIRATION_TIME_IN_HOURS,
        );

        $this->emailService->sendEmail($email, 'Verify your Movary email address', $message, $smtpConfig);
    }
}
