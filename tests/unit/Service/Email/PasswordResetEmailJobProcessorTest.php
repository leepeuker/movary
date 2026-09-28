<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Email;

use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\JobQueue\JobEntity;
use Movary\Service\ApplicationUrlService;
use Movary\Service\Email\CannotSendEmailException;
use Movary\Service\Email\EmailService;
use Movary\Service\Email\EmailSupport;
use Movary\Service\Email\PasswordResetEmailJobProcessor;
use Movary\Service\Email\PasswordResetEmailRenderer;
use Movary\Service\Email\SmtpConfig;
use Movary\Service\Email\SmtpConfigFactory;
use Movary\Util\Json;
use Movary\ValueObject\RelativeUrl;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass(PasswordResetEmailJobProcessor::class)]
#[AllowMockObjectsWithoutExpectations]
class PasswordResetEmailJobProcessorTest extends TestCase
{
    private ApplicationUrlService|MockObject $applicationUrlServiceMock;

    private EmailService|MockObject $emailServiceMock;

    private EmailSupport|MockObject $emailSupportMock;

    private LoggerInterface|MockObject $loggerMock;

    private PasswordResetEmailRenderer|MockObject $passwordResetEmailRendererMock;

    private SmtpConfig $smtpConfigMock;

    private SmtpConfigFactory|MockObject $smtpConfigFactoryMock;

    private PasswordResetEmailJobProcessor $subject;

    private PasswordResetTokenService|MockObject $tokenServiceMock;

    private UserApi|MockObject $userApiMock;

    private UserEntity|MockObject $userMock;

    protected function setUp() : void
    {
        $this->userMock = $this->createMock(UserEntity::class);
        $this->userMock->method('getEmail')->willReturn('user@example.com');
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->userApiMock->method('findUserById')->willReturn($this->userMock);
        $this->emailSupportMock = $this->createMock(EmailSupport::class);
        $this->emailSupportMock->method('isEnabled')->willReturn(true);
        $this->passwordResetEmailRendererMock = $this->createMock(PasswordResetEmailRenderer::class);
        $this->passwordResetEmailRendererMock->method('render')->willReturn('<html>Reset password</html>');
        $this->tokenServiceMock = $this->createMock(PasswordResetTokenService::class);
        $this->applicationUrlServiceMock = $this->createMock(ApplicationUrlService::class);
        $this->applicationUrlServiceMock->method('hasApplicationUrl')->willReturn(true);
        $this->smtpConfigMock = $this->createMock(SmtpConfig::class);
        $this->smtpConfigFactoryMock = $this->createMock(SmtpConfigFactory::class);
        $this->smtpConfigFactoryMock->method('create')->willReturn($this->smtpConfigMock);
        $this->emailServiceMock = $this->createMock(EmailService::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->subject = new PasswordResetEmailJobProcessor(
            $this->userApiMock,
            $this->emailSupportMock,
            $this->passwordResetEmailRendererMock,
            $this->tokenServiceMock,
            $this->applicationUrlServiceMock,
            $this->smtpConfigFactoryMock,
            $this->emailServiceMock,
            $this->loggerMock,
        );
    }

    public function testExecuteJobCreatesTokenAndSendsPasswordResetEmail() : void
    {
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('createTokenIfAllowed')
            ->with(12)
            ->willReturn('reset-token');
        $this->tokenServiceMock->expects(self::never())->method('createToken');
        $this->applicationUrlServiceMock
            ->expects(self::once())
            ->method('createApplicationUrl')
            ->with(self::callback(
                static fn(RelativeUrl $url) => (string)$url === '/reset-password?token=reset-token',
            ))
            ->willReturn('https://movary.example/reset-password?token=reset-token');
        $this->passwordResetEmailRendererMock
            ->expects(self::once())
            ->method('render')
            ->with('https://movary.example/reset-password?token=reset-token', 15);
        $this->emailServiceMock
            ->expects(self::once())
            ->method('sendEmail')
            ->with(
                'user@example.com',
                'Reset your Movary password',
                '<html>Reset password</html>',
                $this->smtpConfigMock,
            );

        $this->subject->executeJob($this->createJob(false));
    }

    public function testExecuteAdminJobCreatesTokenWithoutCooldown() : void
    {
        $this->tokenServiceMock->expects(self::never())->method('createTokenIfAllowed');
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('createToken')
            ->with(12)
            ->willReturn('replacement-token');
        $this->applicationUrlServiceMock
            ->method('createApplicationUrl')
            ->willReturn('https://movary.example/reset-password?token=replacement-token');
        $this->emailServiceMock->expects(self::once())->method('sendEmail');

        $this->subject->executeJob($this->createJob(true));
    }

    public function testExecuteJobDoesNotSendEmailDuringCooldown() : void
    {
        $this->tokenServiceMock->method('createTokenIfAllowed')->willReturn(null);
        $this->emailServiceMock->expects(self::never())->method('sendEmail');
        $this->loggerMock
            ->expects(self::once())
            ->method('info')
            ->with(
                'Password reset email not sent because token creation is in cooldown.',
                ['userId' => 12],
            );

        $this->subject->executeJob($this->createJob(false));
    }

    public function testExecuteJobDeletesTokenAndRethrowsWhenSendingFails() : void
    {
        $this->tokenServiceMock->method('createTokenIfAllowed')->willReturn('reset-token');
        $this->applicationUrlServiceMock
            ->method('createApplicationUrl')
            ->willReturn('https://movary.example/reset-password?token=reset-token');
        $this->emailServiceMock
            ->method('sendEmail')
            ->willThrowException(new CannotSendEmailException('SMTP failed.'));
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('deleteToken')
            ->with('reset-token');

        $this->expectException(CannotSendEmailException::class);
        $this->expectExceptionMessage('SMTP failed.');

        $this->subject->executeJob($this->createJob(false));
    }

    public function testExecuteJobRequiresUserId() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing parameter: userId');

        $this->subject->executeJob($this->createJob(false, null));
    }

    private function createJob(bool $ignoreCooldown, ?int $userId = 12) : JobEntity
    {
        return JobEntity::createFromArray([
            'id' => 5,
            'job_type' => 'password_reset_email',
            'job_status' => 'waiting',
            'user_id' => $userId,
            'parameters' => Json::encode(['ignoreCooldown' => $ignoreCooldown]),
            'updated_at' => null,
            'created_at' => '2026-09-28 12:00:00',
        ]);
    }
}
