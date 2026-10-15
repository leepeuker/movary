<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\UserUpdate;
use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Service\EmailVerificationRequestService;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(UserUpdate::class)]
class UserUpdateTest extends TestCase
{
    public function testReportsPasswordMinimumFromDomainException() : void
    {
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::once())->method('fetchUser')->willReturn($this->createStub(UserEntity::class));
        $userApi
            ->expects(self::once())
            ->method('updatePassword')
            ->willThrowException(new PasswordTooShort(15));
        $command = new UserUpdate(
            $userApi,
            $this->createStub(LoggerInterface::class),
            $this->createStub(EmailVerificationRequestService::class),
        );
        (new Application())->add($command);
        $tester = new CommandTester($command);

        $result = $tester->execute(['userId' => 7, '--password' => 'short']);

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString(
            'Password must contain at least 15 characters',
            $tester->getDisplay(),
        );
    }

    public function testSchedulesVerificationForPendingEmailChange() : void
    {
        $userApi = $this->createMock(UserApi::class);
        $user = $this->createStub(UserEntity::class);
        $userApi->method('fetchUser')->willReturn($user);
        $userApi->expects(self::once())->method('updateEmail')->with(7, 'new@example.com')->willReturn(true);
        $userApi->expects(self::once())->method('findUserById')->with(7)->willReturn($user);
        $requestService = $this->createMock(EmailVerificationRequestService::class);
        $requestService->expects(self::once())->method('requestForUser')->with($user)->willReturn(true);
        $command = new UserUpdate($userApi, $this->createStub(LoggerInterface::class), $requestService);
        (new Application())->add($command);
        $tester = new CommandTester($command);

        $result = $tester->execute(['userId' => 7, '--email' => 'new@example.com']);

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('Updated user.', $tester->getDisplay());
    }

    public function testReportsDeliveryFailureWithoutRevertingPendingEmail() : void
    {
        $userApi = $this->createMock(UserApi::class);
        $user = $this->createStub(UserEntity::class);
        $userApi->method('fetchUser')->willReturn($user);
        $userApi->expects(self::once())->method('updateEmail')->willReturn(true);
        $userApi->method('findUserById')->willReturn($user);
        $requestService = $this->createStub(EmailVerificationRequestService::class);
        $requestService->method('requestForUser')->willReturn(false);
        $command = new UserUpdate($userApi, $this->createStub(LoggerInterface::class), $requestService);
        (new Application())->add($command);
        $tester = new CommandTester($command);

        $result = $tester->execute(['userId' => 7, '--email' => 'typo@example.com']);

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString('current email remains active', $tester->getDisplay());
    }
}
