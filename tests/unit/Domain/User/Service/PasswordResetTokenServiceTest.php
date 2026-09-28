<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\Domain\User\Service\Validator;
use Movary\Domain\User\UserRepository;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Domain\User\Service\PasswordResetTokenService::class)]
#[AllowMockObjectsWithoutExpectations]
class PasswordResetTokenServiceTest extends TestCase
{
    private const string TOKEN = 'password-reset-token';

    private MockObject|UserRepository $repositoryMock;

    private MockObject|Validator $validatorMock;

    private PasswordResetTokenService $subject;

    protected function setUp() : void
    {
        $this->repositoryMock = $this->createMock(UserRepository::class);
        $this->validatorMock = $this->createMock(Validator::class);
        $this->subject = new PasswordResetTokenService($this->repositoryMock, $this->validatorMock);
    }

    public function testResetPasswordValidatesAndHashesPassword() : void
    {
        $this->validatorMock
            ->expects(self::once())
            ->method('ensurePasswordIsValid')
            ->with('new-password');
        $this->repositoryMock
            ->expects(self::once())
            ->method('resetPasswordWithToken')
            ->with(
                hash('sha256', self::TOKEN),
                self::callback(static fn(string $hash) => password_verify('new-password', $hash)),
                self::isInstanceOf(DateTime::class),
            )
            ->willReturn(true);

        self::assertTrue($this->subject->resetPassword(self::TOKEN, 'new-password'));
    }

    public function testCreateTokenIfAllowedCreatesTokenWithoutExistingToken() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('findPasswordResetTokenCreationDate')
            ->with(12)
            ->willReturn(null);
        $this->repositoryMock
            ->expects(self::once())
            ->method('replacePasswordResetToken');

        $token = $this->subject->createTokenIfAllowed(12);
        self::assertNotNull($token);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function testCreateTokenIfAllowedCreatesTokenAfterCooldown() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('findPasswordResetTokenCreationDate')
            ->with(12)
            ->willReturn(DateTime::createFromString('-61 seconds'));
        $this->repositoryMock
            ->expects(self::once())
            ->method('replacePasswordResetToken');

        $token = $this->subject->createTokenIfAllowed(12);
        self::assertNotNull($token);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function testCreateTokenIfAllowedReturnsNullDuringCooldown() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('findPasswordResetTokenCreationDate')
            ->with(12)
            ->willReturn(DateTime::createFromString('-30 seconds'));
        $this->repositoryMock->expects(self::never())->method('replacePasswordResetToken');

        self::assertNull($this->subject->createTokenIfAllowed(12));
    }

    public function testCreateTokenStoresHashAndReturnsRawToken() : void
    {
        $storedTokenHash = null;
        $this->repositoryMock
            ->expects(self::once())
            ->method('replacePasswordResetToken')
            ->with(
                12,
                self::callback(static function (string $tokenHash) use (&$storedTokenHash) : bool {
                    $storedTokenHash = $tokenHash;

                    return strlen($tokenHash) === 64;
                }),
                self::callback(
                    static fn(DateTime $expirationDate) => $expirationDate->isAfter(DateTime::create()),
                ),
            );

        $token = $this->subject->createToken(12);

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        self::assertSame(hash('sha256', $token), $storedTokenHash);
    }

    public function testFindUserIdByTokenReturnsValidTokenUser() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('findPasswordResetTokenData')
            ->with(hash('sha256', self::TOKEN))
            ->willReturn([
                'userId' => 12,
                'expirationDate' => DateTime::createFromString('+1 minute'),
                'createdAt' => DateTime::createFromString('-1 minute'),
            ]);
        $this->repositoryMock->expects(self::never())->method('deletePasswordResetToken');

        self::assertSame(12, $this->subject->findUserIdByToken(self::TOKEN));
    }

    public function testFindUserIdByTokenDeletesExpiredToken() : void
    {
        $tokenHash = hash('sha256', self::TOKEN);
        $this->repositoryMock
            ->expects(self::once())
            ->method('findPasswordResetTokenData')
            ->with($tokenHash)
            ->willReturn([
                'userId' => 12,
                'expirationDate' => DateTime::createFromString('-1 minute'),
                'createdAt' => DateTime::createFromString('-16 minutes'),
            ]);
        $this->repositoryMock
            ->expects(self::once())
            ->method('deletePasswordResetToken')
            ->with($tokenHash);

        self::assertNull($this->subject->findUserIdByToken(self::TOKEN));
    }

    public function testFindUserIdByTokenReturnsNullForUnknownToken() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('findPasswordResetTokenData')
            ->with(hash('sha256', self::TOKEN))
            ->willReturn(null);

        self::assertNull($this->subject->findUserIdByToken(self::TOKEN));
    }

    public function testFetchPendingTokens() : void
    {
        $pendingTokens = [[
            'userId' => 12,
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'expirationDate' => DateTime::createFromString('+15 minutes'),
            'createdAt' => DateTime::create(),
        ]];
        $this->repositoryMock
            ->expects(self::once())
            ->method('fetchPendingPasswordResetTokens')
            ->with(self::isInstanceOf(DateTime::class))
            ->willReturn($pendingTokens);

        self::assertSame($pendingTokens, $this->subject->fetchPendingTokens());
    }

    public function testDeleteTokenHashesToken() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('deletePasswordResetToken')
            ->with(hash('sha256', self::TOKEN));

        $this->subject->deleteToken(self::TOKEN);
    }

    public function testDeleteTokenForUser() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('deletePasswordResetTokenForUser')
            ->with(12);

        $this->subject->deleteTokenForUser(12);
    }
}
