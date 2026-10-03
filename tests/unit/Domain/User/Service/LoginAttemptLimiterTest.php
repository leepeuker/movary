<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Exception\LoginAttemptLimitReached;
use Movary\Domain\User\Service\LoginAttemptLimiter;
use Movary\Domain\User\UserRepository;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(LoginAttemptLimiter::class)]
class LoginAttemptLimiterTest extends TestCase
{
    private MockObject|UserRepository $repositoryMock;

    private LoginAttemptLimiter $subject;

    protected function setUp() : void
    {
        $this->repositoryMock = $this->createMock(UserRepository::class);
        $this->subject = new LoginAttemptLimiter($this->repositoryMock);
    }

    public function testAllowsAttemptWhenNoThresholdIsReached() : void
    {
        $this->repositoryMock
            ->expects(self::exactly(2))
            ->method('findLoginAttemptThresholdDate')
            ->willReturn(null);

        $this->subject->ensureAttemptIsAllowed('User@Example.com', '127.0.0.1');
    }

    public function testRejectsAttemptWhenAccountThresholdIsReached() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('findLoginAttemptThresholdDate')
            ->willReturn(DateTime::create());

        $this->expectException(LoginAttemptLimitReached::class);

        $this->subject->ensureAttemptIsAllowed('user@example.com', null);
    }

    public function testRecordsAttemptsForAccountAndClientIp() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('deleteLoginAttemptsBefore');
        $this->repositoryMock
            ->expects(self::exactly(2))
            ->method('createLoginAttempt')
            ->withAnyParameters();

        $this->subject->recordFailedAttempt('User@Example.com', '127.0.0.1');
    }

    public function testResetsOnlyAccountAttemptsAfterSuccessfulLogin() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('deleteLoginAttemptsForSubject')
            ->with('account', hash('sha256', 'user@example.com'));

        $this->subject->resetAccountAttempts('User@Example.com');
    }
}
