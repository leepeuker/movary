<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\UserPasswordRevokeReset;
use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(UserPasswordRevokeReset::class)]
#[AllowMockObjectsWithoutExpectations]
class UserPasswordRevokeResetTest extends TestCase
{
    private PasswordResetTokenService&MockObject $tokenServiceMock;

    private CommandTester $tester;

    private UserApi&MockObject $userApiMock;

    protected function setUp() : void
    {
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->tokenServiceMock = $this->createMock(PasswordResetTokenService::class);
        $this->tester = new CommandTester(new UserPasswordRevokeReset(
            $this->userApiMock,
            $this->tokenServiceMock,
            $this->createMock(LoggerInterface::class),
        ));
    }

    public function testRevokesResetForUser() : void
    {
        $this->userApiMock
            ->expects(self::once())
            ->method('findUserById')
            ->with(12)
            ->willReturn($this->createMock(UserEntity::class));
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('deleteTokenForUser')
            ->with(12);

        $result = $this->tester->execute(['userId' => 12]);

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('Password reset revoked.', $this->tester->getDisplay());
    }

    public function testRejectsUnknownUser() : void
    {
        $this->userApiMock->method('findUserById')->willReturn(null);
        $this->tokenServiceMock->expects(self::never())->method('deleteTokenForUser');

        $result = $this->tester->execute(['userId' => 99]);

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString('User id does not exist: 99', $this->tester->getDisplay());
    }
}
