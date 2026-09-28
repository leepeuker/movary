<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Service\PasswordResetRequestService;
use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Service\ApplicationUrlService;
use Movary\Service\Email\CannotSendEmailException;
use Movary\Service\Email\EmailService;
use Movary\Service\Email\EmailSupport;
use Movary\Service\Email\SmtpConfig;
use Movary\Service\Email\SmtpConfigFactory;
use Movary\ValueObject\RelativeUrl;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(PasswordResetRequestService::class)]
#[AllowMockObjectsWithoutExpectations]
class PasswordResetRequestServiceTest extends TestCase
{
    private ApplicationUrlService|MockObject $applicationUrlServiceMock;

    private EmailService|MockObject $emailServiceMock;

    private bool $emailSupportEnabled = true;

    private EmailSupport|MockObject $emailSupportMock;

    private LoggerInterface|MockObject $loggerMock;

    private SmtpConfigFactory|MockObject $smtpConfigFactoryMock;

    private PasswordResetRequestService $subject;

    private PasswordResetTokenService|MockObject $tokenServiceMock;

    private UserApi|MockObject $userApiMock;

    protected function setUp() : void
    {
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->tokenServiceMock = $this->createMock(PasswordResetTokenService::class);
        $this->applicationUrlServiceMock = $this->createMock(ApplicationUrlService::class);
        $this->smtpConfigFactoryMock = $this->createMock(SmtpConfigFactory::class);
        $this->emailSupportMock = $this->createMock(EmailSupport::class);
        $this->emailSupportMock
            ->method('isEnabled')
            ->willReturnCallback(fn() : bool => $this->emailSupportEnabled);
        $this->emailServiceMock = $this->createMock(EmailService::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->subject = new PasswordResetRequestService(
            $this->userApiMock,
            $this->emailSupportMock,
            $this->tokenServiceMock,
            $this->applicationUrlServiceMock,
            $this->smtpConfigFactoryMock,
            $this->emailServiceMock,
            $this->loggerMock,
        );
    }

    public function testRequestIgnoresInvalidEmail() : void
    {
        $this->applicationUrlServiceMock->method('hasApplicationUrl')->willReturn(true);
        $this->userApiMock->expects(self::never())->method('findUserByEmail');

        $this->subject->request('not-an-email');
    }

    public function testRequestIgnoresUnknownEmail() : void
    {
        $this->applicationUrlServiceMock->method('hasApplicationUrl')->willReturn(true);
        $this->userApiMock
            ->expects(self::once())
            ->method('findUserByEmail')
            ->with('unknown@example.com')
            ->willReturn(null);
        $this->tokenServiceMock->expects(self::never())->method('createTokenIfAllowed');

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Password reset email not send because email does not exist.',
                ['email' => 'unknown@example.com'],
            );
        $this->subject->request(' unknown@example.com ');
    }

    public function testRequestIgnoresProtectedUser() : void
    {
        $this->applicationUrlServiceMock->method('hasApplicationUrl')->willReturn(true);
        $user = $this->createUser(true);
        $this->userApiMock->method('findUserByEmail')->willReturn($user);
        $this->tokenServiceMock->expects(self::never())->method('createTokenIfAllowed');

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Password reset email not send because email does not exist.',
                ['email' => 'user@example.com'],
            );
        $this->subject->request('user@example.com');
    }

    public function testRequestSendsPasswordResetEmail() : void
    {
        $user = $this->createUser(false);
        $smtpConfig = $this->createMock(SmtpConfig::class);
        $this->userApiMock->method('findUserByEmail')->willReturn($user);
        $this->applicationUrlServiceMock->method('hasApplicationUrl')->willReturn(true);
        $this->smtpConfigFactoryMock->method('create')->willReturn($smtpConfig);
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('createTokenIfAllowed')
            ->with(12)
            ->willReturn('reset-token');
        $this->applicationUrlServiceMock
            ->expects(self::once())
            ->method('createApplicationUrl')
            ->with(self::callback(
                static fn(RelativeUrl $url) => (string)$url === '/reset-password?token=reset-token',
            ))
            ->willReturn('https://movary.example/reset-password?token=reset-token');
        $this->emailServiceMock
            ->expects(self::once())
            ->method('sendEmail')
            ->with(
                'user@example.com',
                'Reset your Movary password',
                self::callback(
                    static fn(string $message) => str_contains(
                        $message,
                        'href="https://movary.example/reset-password?token=reset-token"',
                    ),
                ),
                $smtpConfig,
            );

        $this->loggerMock
            ->expects(self::once())
            ->method('info')
            ->with('Password reset email sent.', ['userId' => 12]);
        $this->subject->request('user@example.com');
    }

    public function testRequestForUserReplacesExistingTokenAndSendsEmail() : void
    {
        $user = $this->createUser(false);
        $smtpConfig = $this->createMock(SmtpConfig::class);
        $this->applicationUrlServiceMock->method('hasApplicationUrl')->willReturn(true);
        $this->smtpConfigFactoryMock->method('create')->willReturn($smtpConfig);
        $this->userApiMock->expects(self::never())->method('findUserByEmail');
        $this->tokenServiceMock->expects(self::never())->method('createTokenIfAllowed');
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('createToken')
            ->with(12)
            ->willReturn('replacement-token');
        $this->applicationUrlServiceMock
            ->method('createApplicationUrl')
            ->willReturn('https://movary.example/reset-password?token=replacement-token');
        $this->emailServiceMock
            ->expects(self::once())
            ->method('sendEmail')
            ->with(
                'user@example.com',
                'Reset your Movary password',
                self::stringContains('replacement-token'),
                $smtpConfig,
            );

        self::assertTrue($this->subject->requestForUser($user));
    }

    public function testRequestForUserDeletesReplacementTokenWhenEmailSendingFails() : void
    {
        $user = $this->createUser(false);
        $this->applicationUrlServiceMock->method('hasApplicationUrl')->willReturn(true);
        $this->applicationUrlServiceMock
            ->method('createApplicationUrl')
            ->willReturn('https://movary.example/reset-password?token=replacement-token');
        $this->smtpConfigFactoryMock->method('create')->willReturn($this->createMock(SmtpConfig::class));
        $this->tokenServiceMock->method('createToken')->willReturn('replacement-token');
        $this->emailServiceMock
            ->method('sendEmail')
            ->willThrowException(new CannotSendEmailException('SMTP failed.'));
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('deleteToken')
            ->with('replacement-token');
        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Could not process password reset request.', self::arrayHasKey('exception'));

        self::assertFalse($this->subject->requestForUser($user));
    }

    public function testRequestDoesNotSendEmailDuringCooldown() : void
    {
        $user = $this->createUser(false);
        $this->userApiMock->method('findUserByEmail')->willReturn($user);
        $this->applicationUrlServiceMock->method('hasApplicationUrl')->willReturn(true);
        $this->smtpConfigFactoryMock->method('create')->willReturn($this->createMock(SmtpConfig::class));
        $this->tokenServiceMock->method('createTokenIfAllowed')->willReturn(null);
        $this->emailServiceMock->expects(self::never())->method('sendEmail');

        $this->loggerMock
            ->expects(self::once())
            ->method('info')
            ->with(
                'Password reset email not send because token was could not be created.',
                ['userId' => 12],
            );
        $this->subject->request('user@example.com');
    }

    public function testRequestLogsMissingApplicationUrlWithoutCreatingToken() : void
    {
        $this->userApiMock->expects(self::never())->method('findUserByEmail');
        $this->applicationUrlServiceMock->method('hasApplicationUrl')->willReturn(false);
        $this->tokenServiceMock->expects(self::never())->method('createTokenIfAllowed');
        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Could not process password reset request.', self::arrayHasKey('exception'));

        $this->subject->request('user@example.com');
    }

    public function testRequestDeletesTokenAndLogsWhenEmailSendingFails() : void
    {
        $user = $this->createUser(false);
        $this->userApiMock->method('findUserByEmail')->willReturn($user);
        $this->applicationUrlServiceMock->method('hasApplicationUrl')->willReturn(true);
        $this->applicationUrlServiceMock
            ->method('createApplicationUrl')
            ->willReturn('https://movary.example/reset-password?token=reset-token');
        $this->smtpConfigFactoryMock->method('create')->willReturn($this->createMock(SmtpConfig::class));
        $this->tokenServiceMock->method('createTokenIfAllowed')->willReturn('reset-token');
        $this->emailServiceMock
            ->method('sendEmail')
            ->willThrowException(new CannotSendEmailException('SMTP failed.'));
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('deleteToken')
            ->with('reset-token');
        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Could not process password reset request.', self::arrayHasKey('exception'));

        $this->loggerMock->expects(self::never())->method('info');
        $this->subject->request('user@example.com');
    }

    public function testRequestDoesNotSendEmailWhenEmailSupportIsDisabled() : void
    {
        $this->emailSupportEnabled = false;
        $this->applicationUrlServiceMock->expects(self::never())->method('hasApplicationUrl');
        $this->userApiMock->expects(self::never())->method('findUserByEmail');
        $this->tokenServiceMock->expects(self::never())->method('createTokenIfAllowed');
        $this->emailServiceMock->expects(self::never())->method('sendEmail');
        $this->loggerMock
            ->expects(self::once())
            ->method('info')
            ->with('Password reset email not sent because email support is disabled.');

        $this->subject->request('user@example.com');
    }

    private function createUser(bool $accountChangesDisabled) : UserEntity&MockObject
    {
        $user = $this->createMock(UserEntity::class);
        $user->method('getId')->willReturn(12);
        $user->method('getEmail')->willReturn('user@example.com');
        $user->method('hasCoreAccountChangesDisabled')->willReturn($accountChangesDisabled);

        return $user;
    }
}
