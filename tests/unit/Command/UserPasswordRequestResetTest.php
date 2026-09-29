<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\UserPasswordRequestReset;
use Movary\Domain\User\Service\PasswordResetRequestService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(UserPasswordRequestReset::class)]
#[AllowMockObjectsWithoutExpectations]
class UserPasswordRequestResetTest extends TestCase
{
    private PasswordResetRequestService&MockObject $requestServiceMock;

    private CommandTester $tester;

    private UserApi&MockObject $userApiMock;

    protected function setUp() : void
    {
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->requestServiceMock = $this->createMock(PasswordResetRequestService::class);
        $this->tester = new CommandTester(new UserPasswordRequestReset(
            $this->userApiMock,
            $this->requestServiceMock,
            $this->createMock(LoggerInterface::class),
        ));
    }

    public function testSchedulesPasswordReset() : void
    {
        $user = $this->createUser(false);
        $this->userApiMock
            ->expects(self::once())
            ->method('findUserById')
            ->with(12)
            ->willReturn($user);
        $this->requestServiceMock
            ->expects(self::once())
            ->method('requestForUser')
            ->with($user)
            ->willReturn(true);

        $result = $this->tester->execute(['userId' => 12]);

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('Password reset email queued.', $this->tester->getDisplay());
        self::assertStringContainsString('jobs:process', $this->tester->getDisplay());
    }

    public function testRejectsUnknownUser() : void
    {
        $this->userApiMock->method('findUserById')->willReturn(null);
        $this->requestServiceMock->expects(self::never())->method('requestForUser');

        $result = $this->tester->execute(['userId' => 99]);

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString('User id does not exist: 99', $this->tester->getDisplay());
    }

    public function testRejectsProtectedUser() : void
    {
        $this->userApiMock->method('findUserById')->willReturn($this->createUser(true));
        $this->requestServiceMock->expects(self::never())->method('requestForUser');

        $result = $this->tester->execute(['userId' => 12]);

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString('Password resets are disabled for this user.', $this->tester->getDisplay());
    }

    public function testReportsSchedulingFailure() : void
    {
        $this->userApiMock->method('findUserById')->willReturn($this->createUser(false));
        $this->requestServiceMock->method('requestForUser')->willReturn(false);

        $result = $this->tester->execute(['userId' => 12]);

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString('Check the logs for details.', $this->tester->getDisplay());
    }

    private function createUser(bool $accountChangesDisabled) : UserEntity&MockObject
    {
        $user = $this->createMock(UserEntity::class);
        $user->method('hasCoreAccountChangesDisabled')->willReturn($accountChangesDisabled);

        return $user;
    }
}
