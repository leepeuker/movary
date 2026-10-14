<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Service\EmailVerificationRequestService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\Email\EmailSupport;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass(EmailVerificationRequestService::class)]
#[AllowMockObjectsWithoutExpectations]
class EmailVerificationRequestServiceTest extends TestCase
{
    private bool $emailVerificationAvailable;

    private EmailSupport&MockObject $emailSupportMock;

    private JobQueueApi&MockObject $jobQueueApiMock;

    private LoggerInterface&MockObject $loggerMock;

    private EmailVerificationRequestService $subject;

    private UserApi&MockObject $userApiMock;

    protected function setUp() : void
    {
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->emailSupportMock = $this->createMock(EmailSupport::class);
        $this->emailVerificationAvailable = true;
        $this->emailSupportMock
            ->method('isEmailVerificationAvailable')
            ->willReturnCallback(fn() : bool => $this->emailVerificationAvailable);
        $this->jobQueueApiMock = $this->createMock(JobQueueApi::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->subject = new EmailVerificationRequestService(
            $this->userApiMock,
            $this->emailSupportMock,
            $this->jobQueueApiMock,
            $this->loggerMock,
        );
    }

    public function testRequestRejectsInvalidEmailWithoutLookup() : void
    {
        $this->userApiMock->expects(self::never())->method('findUserByEmail');
        $this->jobQueueApiMock->expects(self::never())->method('addEmailVerificationJob');

        self::assertFalse($this->subject->request('not-an-email'));
    }

    public function testRequestDoesNotRevealUnknownAccount() : void
    {
        $this->userApiMock->method('findUserByEmail')->willReturn(null);
        $this->jobQueueApiMock->expects(self::never())->method('addEmailVerificationJob');

        self::assertFalse($this->subject->request('unknown@example.com'));
    }

    public function testRequestDoesNotScheduleForVerifiedAccount() : void
    {
        $user = $this->createUser(true);
        $this->userApiMock->method('findUserByEmail')->willReturn($user);
        $this->jobQueueApiMock->expects(self::never())->method('addEmailVerificationJob');

        self::assertFalse($this->subject->request('alice@example.com'));
    }

    public function testRequestSchedulesForUnverifiedAccount() : void
    {
        $this->userApiMock->method('findUserByEmail')->willReturn($this->createUser(false));
        $this->jobQueueApiMock
            ->expects(self::once())
            ->method('addEmailVerificationJob')
            ->with(12, false);

        self::assertTrue($this->subject->request(' alice@example.com '));
    }

    public function testRequestForUserCanIgnoreCooldown() : void
    {
        $user = $this->createUser(false);
        $this->userApiMock->expects(self::never())->method('findUserByEmail');
        $this->jobQueueApiMock
            ->expects(self::once())
            ->method('addEmailVerificationJob')
            ->with(12, true);

        self::assertTrue($this->subject->requestForUser($user, true));
    }

    public function testRequestReturnsFalseWhenEmailIsUnavailable() : void
    {
        $this->emailVerificationAvailable = false;
        $this->jobQueueApiMock->expects(self::never())->method('addEmailVerificationJob');

        self::assertFalse($this->subject->requestForUser($this->createUser(false)));
    }

    public function testRequestHandlesQueueFailure() : void
    {
        $this->jobQueueApiMock
            ->method('addEmailVerificationJob')
            ->willThrowException(new RuntimeException('Database unavailable.'));
        $this->loggerMock->expects(self::once())->method('error');

        self::assertFalse($this->subject->requestForUser($this->createUser(false)));
    }

    private function createUser(bool $verified) : UserEntity&MockObject
    {
        $user = $this->createMock(UserEntity::class);
        $user->method('getId')->willReturn(12);
        $user->method('isEmailVerified')->willReturn($verified);

        return $user;
    }
}
