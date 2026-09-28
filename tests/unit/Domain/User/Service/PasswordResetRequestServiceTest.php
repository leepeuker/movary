<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Service\PasswordResetRequestService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\ApplicationUrlService;
use Movary\Service\Email\EmailSupport;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass(PasswordResetRequestService::class)]
#[AllowMockObjectsWithoutExpectations]
class PasswordResetRequestServiceTest extends TestCase
{
    private ApplicationUrlService|MockObject $applicationUrlServiceMock;

    private bool $emailSupportEnabled = true;

    private EmailSupport|MockObject $emailSupportMock;

    private JobQueueApi|MockObject $jobQueueApiMock;

    private bool $hasApplicationUrl = true;

    private LoggerInterface|MockObject $loggerMock;

    private PasswordResetRequestService $subject;

    private UserApi|MockObject $userApiMock;

    protected function setUp() : void
    {
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->emailSupportMock = $this->createMock(EmailSupport::class);
        $this->emailSupportMock
            ->method('isEnabled')
            ->willReturnCallback(fn() : bool => $this->emailSupportEnabled);
        $this->applicationUrlServiceMock = $this->createMock(ApplicationUrlService::class);
        $this->applicationUrlServiceMock
            ->method('hasApplicationUrl')
            ->willReturnCallback(fn() : bool => $this->hasApplicationUrl);
        $this->jobQueueApiMock = $this->createMock(JobQueueApi::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->subject = new PasswordResetRequestService(
            $this->userApiMock,
            $this->emailSupportMock,
            $this->applicationUrlServiceMock,
            $this->jobQueueApiMock,
            $this->loggerMock,
        );
    }

    public function testRequestIgnoresInvalidEmail() : void
    {
        $this->userApiMock->expects(self::never())->method('findUserByEmail');
        $this->jobQueueApiMock->expects(self::never())->method('addPasswordResetEmailJob');

        self::assertFalse($this->subject->request('not-an-email'));
    }

    public function testRequestIgnoresUnknownEmail() : void
    {
        $this->userApiMock
            ->expects(self::once())
            ->method('findUserByEmail')
            ->with('unknown@example.com')
            ->willReturn(null);
        $this->jobQueueApiMock->expects(self::never())->method('addPasswordResetEmailJob');
        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Password reset email not scheduled because email does not exist.',
                ['email' => 'unknown@example.com'],
            );

        self::assertFalse($this->subject->request(' unknown@example.com '));
    }

    public function testRequestIgnoresProtectedUser() : void
    {
        $this->userApiMock->method('findUserByEmail')->willReturn($this->createUser(true));
        $this->jobQueueApiMock->expects(self::never())->method('addPasswordResetEmailJob');

        self::assertFalse($this->subject->request('user@example.com'));
    }

    public function testRequestSchedulesPasswordResetEmailJob() : void
    {
        $this->userApiMock->method('findUserByEmail')->willReturn($this->createUser(false));
        $this->jobQueueApiMock
            ->expects(self::once())
            ->method('addPasswordResetEmailJob')
            ->with(12, false);
        $this->loggerMock
            ->expects(self::once())
            ->method('info')
            ->with('Password reset email job scheduled.', ['userId' => 12]);

        self::assertTrue($this->subject->request('user@example.com'));
    }

    public function testRequestForUserSchedulesJobThatIgnoresCooldown() : void
    {
        $user = $this->createUser(false);
        $this->userApiMock->expects(self::never())->method('findUserByEmail');
        $this->jobQueueApiMock
            ->expects(self::once())
            ->method('addPasswordResetEmailJob')
            ->with(12, true);

        self::assertTrue($this->subject->requestForUser($user));
    }

    public function testRequestDoesNotScheduleJobWithoutApplicationUrl() : void
    {
        $this->hasApplicationUrl = false;
        $this->jobQueueApiMock->expects(self::never())->method('addPasswordResetEmailJob');
        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Could not schedule password reset email.', self::arrayHasKey('exception'));

        self::assertFalse($this->subject->request('user@example.com'));
    }

    public function testRequestDoesNotScheduleJobWhenEmailSupportIsDisabled() : void
    {
        $this->emailSupportEnabled = false;
        $this->applicationUrlServiceMock->expects(self::never())->method('hasApplicationUrl');
        $this->jobQueueApiMock->expects(self::never())->method('addPasswordResetEmailJob');
        $this->loggerMock
            ->expects(self::once())
            ->method('info')
            ->with('Password reset email not scheduled because email support is disabled.');

        self::assertFalse($this->subject->request('user@example.com'));
    }

    public function testRequestLogsJobSchedulingFailure() : void
    {
        $this->userApiMock->method('findUserByEmail')->willReturn($this->createUser(false));
        $this->jobQueueApiMock
            ->method('addPasswordResetEmailJob')
            ->willThrowException(new RuntimeException('Database unavailable.'));
        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Could not schedule password reset email.', self::arrayHasKey('exception'));

        self::assertFalse($this->subject->request('user@example.com'));
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
