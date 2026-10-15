<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Service\EmailVerificationTokenService;
use Movary\Domain\User\UserRepository;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmailVerificationTokenService::class)]
class EmailVerificationTokenServiceTest extends TestCase
{
    private UserRepository&MockObject $repositoryMock;

    private EmailVerificationTokenService $subject;

    protected function setUp() : void
    {
        $this->repositoryMock = $this->createMock(UserRepository::class);
        $this->subject = new EmailVerificationTokenService($this->repositoryMock);
    }

    public function testCreateTokenStoresOnlyHashAndEmailSnapshot() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('replaceEmailVerificationToken')
            ->with(
                12,
                'alice@example.com',
                self::callback(static fn(string $hash) : bool => strlen($hash) === 64),
                self::isInstanceOf(DateTime::class),
            );

        $token = $this->subject->createToken(12, 'alice@example.com');

        self::assertSame(64, strlen($token));
        self::assertNotSame(hash('sha256', $token), $token);
    }

    public function testCreateTokenIfAllowedHonorsCooldown() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('findEmailVerificationTokenCreationDate')
            ->with(12)
            ->willReturn(DateTime::createFromString('+30 seconds'));
        $this->repositoryMock->expects(self::never())->method('replaceEmailVerificationToken');

        self::assertNull($this->subject->createTokenIfAllowed(12, 'alice@example.com'));
    }

    public function testCreateTokenIfAllowedReplacesOldToken() : void
    {
        $this->repositoryMock
            ->method('findEmailVerificationTokenCreationDate')
            ->willReturn(DateTime::createFromString('-2 minutes'));
        $this->repositoryMock->expects(self::once())->method('replaceEmailVerificationToken');

        self::assertNotNull($this->subject->createTokenIfAllowed(12, 'alice@example.com'));
    }

    public function testFindUserIdByTokenReturnsUserForValidCurrentEmail() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('findEmailVerificationTokenData')
            ->with(hash('sha256', 'verification-token'))
            ->willReturn([
                'userId' => 12,
                'email' => 'alice@example.com',
                'currentEmail' => 'alice@example.com',
                'pendingEmail' => null,
                'expirationDate' => DateTime::createFromString('+1 hour'),
            ]);
        $this->repositoryMock->expects(self::never())->method('deleteEmailVerificationToken');

        self::assertSame(12, $this->subject->findUserIdByToken('verification-token'));
    }

    public function testFindUserIdByTokenReturnsUserForValidPendingEmail() : void
    {
        $this->repositoryMock
            ->method('findEmailVerificationTokenData')
            ->willReturn([
                'userId' => 12,
                'email' => 'new@example.com',
                'currentEmail' => 'alice@example.com',
                'pendingEmail' => 'new@example.com',
                'expirationDate' => DateTime::createFromString('+1 hour'),
            ]);
        $this->repositoryMock->expects(self::never())->method('deleteEmailVerificationToken');

        self::assertSame(12, $this->subject->findUserIdByToken('verification-token'));
    }

    public function testFindUserIdByTokenDeletesExpiredToken() : void
    {
        $tokenHash = hash('sha256', 'verification-token');
        $this->repositoryMock
            ->method('findEmailVerificationTokenData')
            ->willReturn([
                'userId' => 12,
                'email' => 'alice@example.com',
                'currentEmail' => 'alice@example.com',
                'pendingEmail' => null,
                'expirationDate' => DateTime::createFromString('-1 hour'),
            ]);
        $this->repositoryMock
            ->expects(self::once())
            ->method('deleteEmailVerificationToken')
            ->with($tokenHash);

        self::assertNull($this->subject->findUserIdByToken('verification-token'));
    }

    public function testFindUserIdByTokenDeletesTokenForChangedEmail() : void
    {
        $this->repositoryMock
            ->method('findEmailVerificationTokenData')
            ->willReturn([
                'userId' => 12,
                'email' => 'old@example.com',
                'currentEmail' => 'new@example.com',
                'pendingEmail' => null,
                'expirationDate' => DateTime::createFromString('+1 hour'),
            ]);
        $this->repositoryMock->expects(self::once())->method('deleteEmailVerificationToken');

        self::assertNull($this->subject->findUserIdByToken('verification-token'));
    }

    public function testVerifyEmailForwardsHashedToken() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('verifyEmailWithToken')
            ->with(
                hash('sha256', 'verification-token'),
                self::isInstanceOf(DateTime::class),
            )
            ->willReturn(true);

        self::assertTrue($this->subject->verifyEmail('verification-token'));
    }

    public function testDeleteTokenForwardsHashedToken() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('deleteEmailVerificationToken')
            ->with(hash('sha256', 'verification-token'));

        $this->subject->deleteToken('verification-token');
    }
}
