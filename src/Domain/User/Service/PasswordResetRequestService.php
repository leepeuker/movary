<?php declare(strict_types=1);

namespace Movary\Domain\User\Service;

use Movary\Domain\User\UserApi;
use Movary\Service\ApplicationUrlService;
use Movary\Service\Email\EmailService;
use Movary\Service\Email\SmtpConfigFactory;
use Movary\ValueObject\RelativeUrl;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class PasswordResetRequestService
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly PasswordResetTokenService $tokenService,
        private readonly ApplicationUrlService $applicationUrlService,
        private readonly SmtpConfigFactory $smtpConfigFactory,
        private readonly EmailService $emailService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function request(string $email) : void
    {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        $token = null;

        try {
            $user = $this->userApi->findUserByEmail($email);
            if ($user === null || $user->hasCoreAccountChangesDisabled() === true) {
                return;
            }

            if ($this->applicationUrlService->hasApplicationUrl() === false) {
                throw new RuntimeException('APPLICATION_URL must be configured to send password reset emails.');
            }

            $smtpConfig = $this->smtpConfigFactory->create();
            $token = $this->tokenService->createTokenIfAllowed($user->getId());
            if ($token === null) {
                return;
            }

            $resetUrl = $this->applicationUrlService->createApplicationUrl(
                RelativeUrl::create('/reset-password?token=' . rawurlencode($token)),
            );
            $escapedResetUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $message = '<p>A password reset was requested for your Movary account.</p>'
                . '<p><a href="' . $escapedResetUrl . '">Reset password</a></p>'
                . '<p>If you did not request this, you can ignore this email.</p>';

            $this->emailService->sendEmail($email, 'Reset your Movary password', $message, $smtpConfig);
        } catch (Throwable $exception) {
            if ($token !== null) {
                try {
                    $this->tokenService->deleteToken($token);
                } catch (Throwable $cleanupException) {
                    $this->logger->error(
                        'Could not remove password reset token after a failed request.',
                        ['exception' => $cleanupException],
                    );
                }
            }

            $this->logger->error('Could not process password reset request.', ['exception' => $exception]);
        }
    }
}
