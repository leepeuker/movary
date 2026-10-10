<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use InvalidArgumentException;
use Movary\Domain\User\Service\PersonalApiTokenService;
use Movary\Domain\User\UserRepository;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(PersonalApiTokenService::class)]
class PersonalApiTokenServiceTest extends TestCase
{
    private MockObject|UserRepository $repositoryMock;

    private PersonalApiTokenService $subject;

    protected function setUp() : void
    {
        $this->repositoryMock = $this->createMock(UserRepository::class);
        $this->subject = new PersonalApiTokenService($this->repositoryMock);
    }

    public function testCreatesNonExpiringTokenAndStoresOnlyItsHash() : void
    {
        $storedHash = null;
        $this->repositoryMock
            ->expects(self::once())
            ->method('createPersonalApiToken')
            ->with(
                12,
                'Home automation',
                self::callback(static function (string $hash) use (&$storedHash) : bool {
                    $storedHash = $hash;

                    return strlen($hash) === 64;
                }),
                self::matchesRegularExpression('/\Amvy_pat_v1_[A-Za-z0-9_-]{8}\z/D'),
                self::isInstanceOf(DateTime::class),
                null,
            );

        $createdToken = $this->subject->createToken(12, '  Home automation  ', null);
        $token = $createdToken['token'];
        $decodedSecret = base64_decode(
            strtr(substr($token, strlen('mvy_pat_v1_')), '-_', '+/') . '=',
            true,
        );

        self::assertMatchesRegularExpression('/\Amvy_pat_v1_[A-Za-z0-9_-]{43}\z/D', $token);
        self::assertIsString($decodedSecret);
        self::assertSame(32, strlen($decodedSecret));
        self::assertSame(hash('sha256', $token), $storedHash);
        self::assertNotSame($token, $storedHash);
        self::assertNull($createdToken['expiresAt']);
    }

    #[DataProvider('expirationProvider')]
    public function testCreatesTokenWithSupportedExpiration(int $expirationDays) : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('createPersonalApiToken')
            ->with(
                12,
                'CLI',
                self::callback(static fn(string $hash) : bool => strlen($hash) === 64),
                self::callback(static fn(string $prefix) : bool => strlen($prefix) === 19),
                self::isInstanceOf(DateTime::class),
                self::callback(
                    static fn(DateTime $expiresAt) : bool => $expiresAt->format('Y-m-d')
                        === DateTime::createFromString('+' . $expirationDays . ' days')->format('Y-m-d'),
                ),
            );

        $createdToken = $this->subject->createToken(12, 'CLI', $expirationDays);

        self::assertInstanceOf(DateTime::class, $createdToken['expiresAt']);
    }

    /** @return array<string, array{int}> */
    public static function expirationProvider() : array
    {
        return [
            '30 days' => [30],
            '90 days' => [90],
            '365 days' => [365],
        ];
    }

    #[DataProvider('invalidNameProvider')]
    public function testRejectsInvalidName(string $name) : void
    {
        $this->repositoryMock->expects(self::never())->method('createPersonalApiToken');
        $this->expectException(InvalidArgumentException::class);

        $this->subject->createToken(12, $name, 90);
    }

    /** @return array<string, array{string}> */
    public static function invalidNameProvider() : array
    {
        return [
            'empty' => ['  '],
            'too long' => [str_repeat('a', 101)],
        ];
    }

    public function testRejectsUnsupportedExpiration() : void
    {
        $this->repositoryMock->expects(self::never())->method('createPersonalApiToken');
        $this->expectException(InvalidArgumentException::class);

        $this->subject->createToken(12, 'CLI', 31);
    }

    #[DataProvider('supportedTokenProvider')]
    public function testFindsUserForSupportedToken(string $token) : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('findPersonalApiTokenData')
            ->with(hash('sha256', $token))
            ->willReturn([
                'id' => 4,
                'userId' => 12,
                'expiresAt' => null,
                'lastUsedAt' => null,
            ]);
        $this->repositoryMock
            ->expects(self::once())
            ->method('updatePersonalApiTokenLastUsedAt')
            ->with(4, self::isInstanceOf(DateTime::class), self::isInstanceOf(DateTime::class));

        self::assertSame(12, $this->subject->findUserIdByToken($token));
    }

    /** @return array<string, array{string}> */
    public static function supportedTokenProvider() : array
    {
        return [
            'versioned' => ['mvy_pat_v1_' . str_repeat('a', 43)],
            'legacy hexadecimal' => [str_repeat('a', 32)],
            'legacy UUID' => ['12345678-1234-1234-1234-123456789012'],
        ];
    }

    public function testRejectsMalformedTokenWithoutLookingItUp() : void
    {
        $this->repositoryMock->expects(self::never())->method('findPersonalApiTokenData');

        self::assertNull($this->subject->findUserIdByToken('mvy_pat_v1_too-short'));
    }

    public function testRejectsUnknownToken() : void
    {
        $token = 'mvy_pat_v1_' . str_repeat('a', 43);
        $this->repositoryMock
            ->expects(self::once())
            ->method('findPersonalApiTokenData')
            ->with(hash('sha256', $token))
            ->willReturn(null);

        self::assertNull($this->subject->findUserIdByToken($token));
    }

    public function testRejectsExpiredToken() : void
    {
        $token = 'mvy_pat_v1_' . str_repeat('a', 43);
        $this->repositoryMock->expects(self::never())->method('updatePersonalApiTokenLastUsedAt');
        $this->repositoryMock
            ->expects(self::once())
            ->method('findPersonalApiTokenData')
            ->willReturn([
                'id' => 4,
                'userId' => 12,
                'expiresAt' => DateTime::createFromString('-1 minute'),
                'lastUsedAt' => null,
            ]);

        self::assertNull($this->subject->findUserIdByToken($token));
    }

    public function testUpdatesStaleLastUsedTimestamp() : void
    {
        $token = 'mvy_pat_v1_' . str_repeat('a', 43);
        $this->repositoryMock
            ->method('findPersonalApiTokenData')
            ->willReturn([
                'id' => 4,
                'userId' => 12,
                'expiresAt' => null,
                'lastUsedAt' => DateTime::createFromString('-16 minutes'),
            ]);
        $this->repositoryMock
            ->expects(self::once())
            ->method('updatePersonalApiTokenLastUsedAt')
            ->with(4, self::isInstanceOf(DateTime::class), self::isInstanceOf(DateTime::class));

        self::assertSame(12, $this->subject->findUserIdByToken($token));
    }

    public function testDoesNotUpdateRecentLastUsedTimestamp() : void
    {
        $token = 'mvy_pat_v1_' . str_repeat('a', 43);
        $this->repositoryMock
            ->method('findPersonalApiTokenData')
            ->willReturn([
                'id' => 4,
                'userId' => 12,
                'expiresAt' => null,
                'lastUsedAt' => DateTime::createFromString('-14 minutes'),
            ]);
        $this->repositoryMock->expects(self::never())->method('updatePersonalApiTokenLastUsedAt');

        self::assertSame(12, $this->subject->findUserIdByToken($token));
    }

    public function testFetchesTokenMetadataForUser() : void
    {
        $tokens = [[
            'id' => 4,
            'name' => 'CLI',
            'tokenPrefix' => 'mvy_pat_v1_abcdefgh',
            'createdAt' => DateTime::createFromString('2026-10-10 12:00:00'),
            'lastUsedAt' => null,
            'expiresAt' => DateTime::createFromString('2027-10-10 12:00:00'),
        ]];
        $this->repositoryMock
            ->expects(self::once())
            ->method('fetchPersonalApiTokensPaginated')
            ->with(12, 20, 40)
            ->willReturn($tokens);

        self::assertSame($tokens, $this->subject->fetchTokensPaginated(12, 20, 40));
    }

    public function testCountsTokensForUser() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('countPersonalApiTokens')
            ->with(12)
            ->willReturn(3);

        self::assertSame(3, $this->subject->countTokens(12));
    }

    public function testRevokesOneOwnedToken() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('deletePersonalApiToken')
            ->with(12, 4);

        $this->subject->revokeToken(12, 4);
    }

    public function testRevokesAllOwnedTokens() : void
    {
        $this->repositoryMock
            ->expects(self::once())
            ->method('deleteAllPersonalApiTokens')
            ->with(12);

        $this->subject->revokeAllTokens(12);
    }
}
