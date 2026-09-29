<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\UserPasswordSet;
use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(UserPasswordSet::class)]
#[AllowMockObjectsWithoutExpectations]
class UserPasswordSetTest extends TestCase
{
    private PasswordResetTokenService&MockObject $tokenServiceMock;

    private CommandTester $tester;

    private UserApi&MockObject $userApiMock;

    protected function setUp() : void
    {
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->tokenServiceMock = $this->createMock(PasswordResetTokenService::class);
        $command = new UserPasswordSet(
            $this->userApiMock,
            $this->tokenServiceMock,
            $this->createMock(LoggerInterface::class),
        );
        $application = new Application();
        $application->add($command);
        $this->tester = new CommandTester($command);
    }

    public function testSetsPasswordAndRevokesPendingReset() : void
    {
        $this->userApiMock
            ->expects(self::once())
            ->method('findUserById')
            ->with(12)
            ->willReturn($this->createMock(UserEntity::class));
        $this->userApiMock
            ->expects(self::once())
            ->method('updatePassword')
            ->with(12, 'new-password');
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('deleteTokenForUser')
            ->with(12);
        $this->tester->setInputs(['new-password', 'new-password']);

        $result = $this->tester->execute(['userId' => 12]);

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('Password updated.', $this->tester->getDisplay());
        self::assertStringNotContainsString('new-password', $this->tester->getDisplay());
    }

    public function testRejectsMismatchedPasswords() : void
    {
        $this->userApiMock->method('findUserById')->willReturn($this->createMock(UserEntity::class));
        $this->userApiMock->expects(self::never())->method('updatePassword');
        $this->tokenServiceMock->expects(self::never())->method('deleteTokenForUser');
        $this->tester->setInputs(['new-password', 'different-password']);

        $result = $this->tester->execute(['userId' => 12]);

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString('Passwords do not match.', $this->tester->getDisplay());
    }

    public function testReportsPasswordPolicyFailure() : void
    {
        $this->userApiMock->method('findUserById')->willReturn($this->createMock(UserEntity::class));
        $this->userApiMock
            ->method('updatePassword')
            ->willThrowException(new PasswordTooShort(8));
        $this->tokenServiceMock->expects(self::never())->method('deleteTokenForUser');
        $this->tester->setInputs(['short', 'short']);

        $result = $this->tester->execute(['userId' => 12]);

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString('Password must contain at least 8 characters.', $this->tester->getDisplay());
    }

    public function testRejectsUnknownUserBeforePrompting() : void
    {
        $this->userApiMock->method('findUserById')->willReturn(null);
        $this->userApiMock->expects(self::never())->method('updatePassword');

        $result = $this->tester->execute(['userId' => 99]);

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString('User id does not exist: 99', $this->tester->getDisplay());
    }

    public function testRejectsNonInteractiveInput() : void
    {
        $this->userApiMock->method('findUserById')->willReturn($this->createMock(UserEntity::class));
        $this->userApiMock->expects(self::never())->method('updatePassword');

        $result = $this->tester->execute(['userId' => 12], ['interactive' => false]);

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString('requires an interactive terminal.', $this->tester->getDisplay());
    }
}
