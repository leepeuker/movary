<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\UserPasswordListResets;
use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(UserPasswordListResets::class)]
#[AllowMockObjectsWithoutExpectations]
class UserPasswordListResetsTest extends TestCase
{
    private PasswordResetTokenService&MockObject $tokenServiceMock;

    private CommandTester $tester;

    protected function setUp() : void
    {
        $this->tokenServiceMock = $this->createMock(PasswordResetTokenService::class);
        $this->tester = new CommandTester(new UserPasswordListResets(
            $this->tokenServiceMock,
            $this->createMock(LoggerInterface::class),
        ));
    }

    public function testReportsEmptyList() : void
    {
        $this->tokenServiceMock->method('fetchPendingTokens')->willReturn([]);

        $result = $this->tester->execute([]);

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('No pending password resets.', $this->tester->getDisplay());
    }

    public function testListsSafeResetMetadata() : void
    {
        $this->tokenServiceMock->method('fetchPendingTokens')->willReturn([[
            'userId' => 12,
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'createdAt' => DateTime::createFromString('2026-09-28 12:00:00'),
            'expirationDate' => DateTime::createFromString('2026-09-28 12:15:00'),
        ]]);

        $result = $this->tester->execute([]);
        $display = $this->tester->getDisplay();

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('User ID', $display);
        self::assertStringContainsString('Alice', $display);
        self::assertStringContainsString('alice@example.com', $display);
        self::assertStringContainsString('2026-09-28 12:15:00', $display);
        self::assertStringNotContainsString('token', strtolower($display));
    }
}
