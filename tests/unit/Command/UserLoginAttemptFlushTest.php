<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\UserLoginAttemptFlush;
use Movary\Domain\User\Service\LoginAttemptLimiter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(UserLoginAttemptFlush::class)]
#[AllowMockObjectsWithoutExpectations]
class UserLoginAttemptFlushTest extends TestCase
{
    private LoginAttemptLimiter&MockObject $loginAttemptLimiterMock;

    private CommandTester $tester;

    protected function setUp() : void
    {
        $this->loginAttemptLimiterMock = $this->createMock(LoginAttemptLimiter::class);
        $command = new UserLoginAttemptFlush(
            $this->loginAttemptLimiterMock,
            $this->createMock(LoggerInterface::class),
        );
        $application = new Application();
        $application->add($command);
        $this->tester = new CommandTester($command);
    }

    public function testRequiresForceWithoutInteraction() : void
    {
        $this->loginAttemptLimiterMock->expects(self::never())->method('flushAttempts');

        self::assertSame(Command::FAILURE, $this->tester->execute([], ['interactive' => false]));
        self::assertStringContainsString('Use --force', $this->tester->getDisplay());
    }

    public function testDoesNotFlushWhenConfirmationIsDeclined() : void
    {
        $this->loginAttemptLimiterMock->expects(self::never())->method('flushAttempts');
        $this->tester->setInputs(['no']);

        self::assertSame(Command::SUCCESS, $this->tester->execute([]));
        self::assertStringContainsString('No login attempts were flushed.', $this->tester->getDisplay());
    }

    public function testFlushesAllAttemptsAfterConfirmation() : void
    {
        $this->loginAttemptLimiterMock->expects(self::once())->method('flushAttempts');
        $this->tester->setInputs(['yes']);

        self::assertSame(Command::SUCCESS, $this->tester->execute([]));
        self::assertStringContainsString('All login attempts flushed.', $this->tester->getDisplay());
    }

    public function testForceFlushesAllAttemptsWithoutInteraction() : void
    {
        $this->loginAttemptLimiterMock->expects(self::once())->method('flushAttempts');

        self::assertSame(Command::SUCCESS, $this->tester->execute(['--force' => true], ['interactive' => false]));
        self::assertStringContainsString('All login attempts flushed.', $this->tester->getDisplay());
    }
}
