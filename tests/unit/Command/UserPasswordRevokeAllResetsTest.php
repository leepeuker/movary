<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\UserPasswordRevokeAllResets;
use Movary\Domain\User\Service\PasswordResetTokenService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(UserPasswordRevokeAllResets::class)]
#[AllowMockObjectsWithoutExpectations]
class UserPasswordRevokeAllResetsTest extends TestCase
{
    private PasswordResetTokenService&MockObject $tokenServiceMock;

    private CommandTester $tester;

    protected function setUp() : void
    {
        $this->tokenServiceMock = $this->createMock(PasswordResetTokenService::class);
        $command = new UserPasswordRevokeAllResets(
            $this->tokenServiceMock,
            $this->createMock(LoggerInterface::class),
        );
        $application = new Application();
        $application->add($command);
        $this->tester = new CommandTester($command);
    }

    public function testRequiresForceWithoutInteraction() : void
    {
        $this->tokenServiceMock->expects(self::never())->method('deleteAllTokens');

        $result = $this->tester->execute([], ['interactive' => false]);

        self::assertSame(Command::FAILURE, $result);
        self::assertStringContainsString('Use --force', $this->tester->getDisplay());
    }

    public function testDoesNotRevokeWhenConfirmationIsDeclined() : void
    {
        $this->tokenServiceMock->expects(self::never())->method('deleteAllTokens');
        $this->tester->setInputs(['no']);

        $result = $this->tester->execute([]);

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('No password resets were revoked.', $this->tester->getDisplay());
    }

    public function testRevokesAllAfterConfirmation() : void
    {
        $this->tokenServiceMock->expects(self::once())->method('deleteAllTokens');
        $this->tester->setInputs(['yes']);

        $result = $this->tester->execute([]);

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('All password resets revoked.', $this->tester->getDisplay());
    }

    public function testForceRevokesAllWithoutInteraction() : void
    {
        $this->tokenServiceMock->expects(self::once())->method('deleteAllTokens');

        $result = $this->tester->execute(['--force' => true], ['interactive' => false]);

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('All password resets revoked.', $this->tester->getDisplay());
    }
}
