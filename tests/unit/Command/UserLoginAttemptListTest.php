<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\UserLoginAttemptList;
use Movary\Domain\User\Service\LoginAttemptLimiter;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(UserLoginAttemptList::class)]
#[AllowMockObjectsWithoutExpectations]
class UserLoginAttemptListTest extends TestCase
{
    private LoginAttemptLimiter&MockObject $loginAttemptLimiterMock;

    private CommandTester $tester;

    protected function setUp() : void
    {
        $this->loginAttemptLimiterMock = $this->createMock(LoginAttemptLimiter::class);
        $this->tester = new CommandTester(new UserLoginAttemptList(
            $this->loginAttemptLimiterMock,
            $this->createMock(LoggerInterface::class),
        ));
    }

    public function testListsLoginAttempts() : void
    {
        $this->loginAttemptLimiterMock->expects(self::once())->method('fetchAttempts')->willReturn([[
            'id' => 1,
            'subjectHash' => 'hash',
            'createdAt' => DateTime::createFromString('2026-10-03 12:00:00'),
        ]]);

        self::assertSame(Command::SUCCESS, $this->tester->execute([]));
        self::assertStringContainsString('Subject hash', $this->tester->getDisplay());
        self::assertStringContainsString('2026-10-03 12:00:00', $this->tester->getDisplay());
    }

    public function testReportsWhenNoLoginAttemptsExist() : void
    {
        $this->loginAttemptLimiterMock->expects(self::once())->method('fetchAttempts')->willReturn([]);

        self::assertSame(Command::SUCCESS, $this->tester->execute([]));
        self::assertStringContainsString('No login attempts.', $this->tester->getDisplay());
    }
}
