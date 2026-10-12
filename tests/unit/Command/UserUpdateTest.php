<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\UserUpdate;
use Movary\Domain\User\Exception\PasswordTooShort;
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
        $command = new UserUpdate($userApi, $this->createStub(LoggerInterface::class));
        (new Application())->add($command);
        $tester = new CommandTester($command);

        $result = $tester->execute(['userId' => 7, '--password' => 'short']);

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString(
            'Password must contain at least 15 characters',
            $tester->getDisplay(),
        );
    }
}
