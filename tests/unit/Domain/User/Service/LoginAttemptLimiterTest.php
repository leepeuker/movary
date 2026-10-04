<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Exception\LoginAttemptLimitReached;
use Movary\Domain\User\Service\LoginAttemptLimiter;
use Movary\Domain\User\UserRepository;
use Movary\Service\ServerSettings;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(LoginAttemptLimiter::class)]
#[AllowMockObjectsWithoutExpectations]
class LoginAttemptLimiterTest extends TestCase
{
    private UserRepository&MockObject $repositoryMock;

    private ServerSettings&MockObject $serverSettingsMock;

    private LoginAttemptLimiter $subject;

    protected function setUp() : void
    {
        $this->repositoryMock = $this->createMock(UserRepository::class);
        $this->serverSettingsMock = $this->createMock(ServerSettings::class);
        $this->serverSettingsMock->method('getLoginAttemptLimit')->willReturn(5);
        $this->serverSettingsMock->method('getLoginAttemptWindowInSeconds')->willReturn(900);
        $this->subject = new LoginAttemptLimiter($this->repositoryMock, $this->serverSettingsMock);
    }

    public function testReservesAttemptWhenLimitIsNotReached() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('findLoginAttemptAboveLimitDate')
            ->with(
                hash('sha256', 'user@example.com'),
                5,
                self::isInstanceOf(DateTime::class),
            )
            ->willReturn(null);
        $this->repositoryMock->expects(self::once())->method('deleteLoginAttemptsBefore');
        $this->repositoryMock->expects(self::once())->method('createLoginAttempt')->willReturn(1);

        self::assertSame(1, $this->subject->reserveAttempt('User@Example.com'));
    }

    public function testRejectsAndReleasesAttemptWhenLimitIsReached() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('findLoginAttemptAboveLimitDate')
            ->willReturn(DateTime::create());
        $this->repositoryMock->expects(self::once())->method('createLoginAttempt')->willReturn(1);
        $this->repositoryMock->expects(self::once())->method('deleteLoginAttempt')->with(1);

        $this->expectException(LoginAttemptLimitReached::class);

        $this->subject->reserveAttempt('user@example.com');
    }

    public function testUsesConfiguredLimitAndWindow() : void
    {
        $this->serverSettingsMock = $this->createMock(ServerSettings::class);
        $this->serverSettingsMock->method('getLoginAttemptLimit')->willReturn(2);
        $this->serverSettingsMock->method('getLoginAttemptWindowInSeconds')->willReturn(60);
        $this->subject = new LoginAttemptLimiter($this->repositoryMock, $this->serverSettingsMock);
        $this->repositoryMock
            ->expects(self::once())
            ->method('findLoginAttemptAboveLimitDate')
            ->with(hash('sha256', 'user@example.com'), 2, self::isInstanceOf(DateTime::class))
            ->willReturn(null);
        $this->repositoryMock->expects(self::once())->method('createLoginAttempt')->willReturn(1);

        self::assertSame(1, $this->subject->reserveAttempt('user@example.com'));
    }

    public function testReleaseAttempt() : void
    {
        $this->repositoryMock->expects(self::once())->method('deleteLoginAttempt')->with(1);

        $this->subject->releaseAttempt(1);
    }

    public function testFetchAttempts() : void
    {
        $attempts = [[
            'id' => 1,
            'subjectHash' => 'hash',
            'createdAt' => DateTime::create(),
        ]];
        $this->repositoryMock->expects(self::once())->method('findLoginAttempts')->willReturn($attempts);

        self::assertSame($attempts, $this->subject->fetchAttempts());
    }

    public function testFlushAttempts() : void
    {
        $this->repositoryMock->expects(self::once())->method('deleteAllLoginAttempts');

        $this->subject->flushAttempts();
    }

    public function testResetsOnlyAccountAttemptsAfterSuccessfulLogin() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('deleteLoginAttemptsForSubject')
            ->with(hash('sha256', 'user@example.com'));

        $this->subject->resetAccountAttempts('User@Example.com');
    }
}
