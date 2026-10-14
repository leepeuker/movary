<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Email;

use Movary\Domain\User\Service\EmailVerificationTokenService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\JobQueue\JobEntity;
use Movary\Service\ApplicationUrlService;
use Movary\Service\Email\CannotSendEmailException;
use Movary\Service\Email\EmailService;
use Movary\Service\Email\EmailSupport;
use Movary\Service\Email\EmailVerificationEmailJobProcessor;
use Movary\Service\Email\EmailVerificationEmailRenderer;
use Movary\Service\Email\SmtpConfig;
use Movary\Service\Email\SmtpConfigFactory;
use Movary\Util\Json;
use Movary\ValueObject\RelativeUrl;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(EmailVerificationEmailJobProcessor::class)]
#[AllowMockObjectsWithoutExpectations]
class EmailVerificationEmailJobProcessorTest extends TestCase
{
    private ApplicationUrlService&MockObject $applicationUrlServiceMock;

    private EmailService&MockObject $emailServiceMock;

    private EmailVerificationEmailRenderer&MockObject $rendererMock;

    private EmailVerificationEmailJobProcessor $subject;

    private EmailVerificationTokenService&MockObject $tokenServiceMock;

    private UserEntity&MockObject $userMock;

    private bool $userIsVerified;

    protected function setUp() : void
    {
        $userApi = $this->createStub(UserApi::class);
        $this->userMock = $this->createMock(UserEntity::class);
        $this->userMock->method('getEmail')->willReturn('alice@example.com');
        $this->userIsVerified = false;
        $this->userMock
            ->method('isEmailVerified')
            ->willReturnCallback(fn() : bool => $this->userIsVerified);
        $userApi->method('findUserById')->willReturn($this->userMock);
        $emailSupport = $this->createStub(EmailSupport::class);
        $emailSupport->method('isEmailVerificationAvailable')->willReturn(true);
        $this->rendererMock = $this->createMock(EmailVerificationEmailRenderer::class);
        $this->rendererMock->method('render')->willReturn('<html>Verify email</html>');
        $this->tokenServiceMock = $this->createMock(EmailVerificationTokenService::class);
        $this->applicationUrlServiceMock = $this->createMock(ApplicationUrlService::class);
        $smtpConfig = $this->createStub(SmtpConfig::class);
        $smtpConfigFactory = $this->createStub(SmtpConfigFactory::class);
        $smtpConfigFactory->method('create')->willReturn($smtpConfig);
        $this->emailServiceMock = $this->createMock(EmailService::class);
        $this->subject = new EmailVerificationEmailJobProcessor(
            $userApi,
            $emailSupport,
            $this->rendererMock,
            $this->tokenServiceMock,
            $this->applicationUrlServiceMock,
            $smtpConfigFactory,
            $this->emailServiceMock,
            $this->createStub(LoggerInterface::class),
        );
    }

    public function testExecuteJobCreatesTokenAndSendsEmail() : void
    {
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('createTokenIfAllowed')
            ->with(12, 'alice@example.com')
            ->willReturn('verification-token');
        $this->applicationUrlServiceMock
            ->expects(self::once())
            ->method('createApplicationUrl')
            ->with(self::callback(
                static fn(RelativeUrl $url) : bool => (string)$url === '/verify-email?token=verification-token',
            ))
            ->willReturn('https://movary.example/verify-email?token=verification-token');
        $this->rendererMock
            ->expects(self::once())
            ->method('render')
            ->with('https://movary.example/verify-email?token=verification-token', 24);
        $this->emailServiceMock
            ->expects(self::once())
            ->method('sendEmail')
            ->with(
                'alice@example.com',
                'Verify your Movary email address',
                '<html>Verify email</html>',
                self::isInstanceOf(SmtpConfig::class),
            );

        $this->subject->executeJob($this->createJob(false));
    }

    public function testExecuteJobDoesNotSendDuringCooldown() : void
    {
        $this->tokenServiceMock->method('createTokenIfAllowed')->willReturn(null);
        $this->emailServiceMock->expects(self::never())->method('sendEmail');

        $this->subject->executeJob($this->createJob(false));
    }

    public function testExecuteJobDoesNotSendForVerifiedUser() : void
    {
        $this->userIsVerified = true;
        $this->tokenServiceMock->expects(self::never())->method('createTokenIfAllowed');
        $this->emailServiceMock->expects(self::never())->method('sendEmail');

        $this->subject->executeJob($this->createJob(false));
    }

    public function testExecuteJobDeletesTokenAfterSendingFailure() : void
    {
        $this->tokenServiceMock->method('createTokenIfAllowed')->willReturn('verification-token');
        $this->applicationUrlServiceMock
            ->method('createApplicationUrl')
            ->willReturn('https://movary.example/verify-email?token=verification-token');
        $this->emailServiceMock
            ->method('sendEmail')
            ->willThrowException(new CannotSendEmailException('SMTP failed.'));
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('deleteToken')
            ->with('verification-token');

        $this->expectException(CannotSendEmailException::class);

        $this->subject->executeJob($this->createJob(false));
    }

    private function createJob(bool $ignoreCooldown) : JobEntity
    {
        return JobEntity::createFromArray([
            'id' => 5,
            'job_type' => 'email_verification',
            'job_status' => 'waiting',
            'user_id' => 12,
            'parameters' => Json::encode(['ignoreCooldown' => $ignoreCooldown]),
            'updated_at' => null,
            'created_at' => '2026-10-14 20:00:00',
        ]);
    }
}
