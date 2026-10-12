<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\UserCreate;
use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\UserApi;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(UserCreate::class)]
class UserCreateTest extends TestCase
{
    public function testReportsPasswordMinimumFromDomainException() : void
    {
        $userApi = $this->createMock(UserApi::class);
        $userApi
            ->expects(self::once())
            ->method('createUser')
            ->willThrowException(new PasswordTooShort(15));
        $command = new UserCreate($userApi, $this->createStub(LoggerInterface::class));
        (new Application())->add($command);
        $tester = new CommandTester($command);

        $result = $tester->execute([
            'email' => 'user@example.com',
            'password' => 'short',
            'name' => 'user',
        ]);

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString(
            'Password must contain at least 15 characters',
            $tester->getDisplay(),
        );
    }
}
