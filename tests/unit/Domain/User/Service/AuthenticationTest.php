<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\LoginAttemptLimiter;
use Movary\Domain\User\Service\PersonalApiTokenService;
use Movary\Domain\User\Exception\InvalidPassword;
use Movary\Domain\User\Service\TwoFactorAuthenticationApi;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserRepository;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\Service\CookieSecurity;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\Util\Cookie;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Domain\User\Service\Authentication::class)]
#[AllowMockObjectsWithoutExpectations]
class AuthenticationTest extends TestCase
{
    private const string TOKEN = 'authentication-token';

    private Authentication $subject;

    private MockObject|UserApi $userApiMock;

    private MockObject|UserRepository $userRepositoryMock;

    private MockObject|CookieSecurity $cookieSecurityMock;

    private MockObject|FlashMessageService $flashMessageServiceMock;

    private MockObject|LoginAttemptLimiter $loginAttemptLimiterMock;

    private MockObject|PersonalApiTokenService $personalApiTokenServiceMock;

    protected function setUp() : void
    {
        unset($_COOKIE['id']);

        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->cookieSecurityMock = $this->createMock(CookieSecurity::class);
        $this->flashMessageServiceMock = $this->createMock(FlashMessageService::class);
        $this->loginAttemptLimiterMock = $this->createMock(LoginAttemptLimiter::class);
        $this->personalApiTokenServiceMock = $this->createMock(PersonalApiTokenService::class);
        $this->subject = new Authentication(
            $this->userRepositoryMock,
            $this->userApiMock,
            $this->personalApiTokenServiceMock,
            $this->createMock(TwoFactorAuthenticationApi::class),
            $this->loginAttemptLimiterMock,
            new Cookie($this->cookieSecurityMock, static fn() => true),
            $this->flashMessageServiceMock,
        );
    }

    protected function tearDown() : void
    {
        unset($_COOKIE['id']);
    }

    public function testAuthenticateApiTokenAcceptsBearerToken() : void
    {
        $_COOKIE['id'] = 'web-session-token';
        $request = $this->createMock(Request::class);
        $request
            ->expects(self::exactly(2))
            ->method('getHeader')
            ->willReturnMap([
                ['Authorization', 'Bearer ' . self::TOKEN],
                ['X-Movary-Token', null],
            ]);
        $this->personalApiTokenServiceMock
            ->expects(self::once())
            ->method('findUserIdByToken')
            ->with(self::TOKEN)
            ->willReturn(12);
        $this->userRepositoryMock->expects(self::never())->method('findAuthTokenData');

        $result = $this->subject->authenticateApiToken($request);

        self::assertSame(12, $result?->getUserId());
        self::assertSame(CredentialType::API_TOKEN, $result?->getCredentialType());
        self::assertSame($result, $this->subject->authenticateApiToken($request));
    }

    public function testAuthenticateApiTokenAcceptsCustomHeaderToken() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getHeader')->willReturnMap([
            ['Authorization', null],
            ['X-Movary-Token', self::TOKEN],
        ]);
        $this->personalApiTokenServiceMock
            ->expects(self::once())
            ->method('findUserIdByToken')
            ->with(self::TOKEN)
            ->willReturn(12);

        self::assertSame(12, $this->subject->authenticateApiToken($request)?->getUserId());
    }

    public function testAuthenticateApiTokenAcceptsCustomHeaderBehindBasicAuthentication() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getHeader')->willReturnMap([
            ['Authorization', 'Basic dXNlcjpwYXNzd29yZA=='],
            ['X-Movary-Token', self::TOKEN],
        ]);
        $this->personalApiTokenServiceMock
            ->expects(self::once())
            ->method('findUserIdByToken')
            ->with(self::TOKEN)
            ->willReturn(12);

        self::assertSame(12, $this->subject->authenticateApiToken($request)?->getUserId());
    }

    public function testAuthenticateApiTokenRejectsBearerAndCustomHeadersTogether() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getHeader')->willReturnMap([
            ['Authorization', 'Bearer ' . self::TOKEN],
            ['X-Movary-Token', self::TOKEN],
        ]);
        $this->personalApiTokenServiceMock->expects(self::never())->method('findUserIdByToken');

        self::assertNull($this->subject->authenticateApiToken($request));
    }

    public function testAuthenticateApiTokenRejectsMalformedBearerHeader() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getHeader')->willReturnMap([
            ['Authorization', 'Bearer'],
            ['X-Movary-Token', null],
        ]);
        $this->personalApiTokenServiceMock->expects(self::never())->method('findUserIdByToken');

        self::assertNull($this->subject->authenticateApiToken($request));
    }

    public function testAuthenticateApiTokenRejectsSessionToken() : void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects(self::exactly(2))
            ->method('getHeader')
            ->willReturnMap([
                ['Authorization', 'Bearer ' . self::TOKEN],
                ['X-Movary-Token', null],
            ]);
        $this->personalApiTokenServiceMock
            ->expects(self::once())
            ->method('findUserIdByToken')
            ->with(self::TOKEN)
            ->willReturn(null);
        $this->userRepositoryMock->expects(self::never())->method('findAuthTokenData');

        self::assertNull($this->subject->authenticateApiToken($request));
        self::assertNull($this->subject->authenticateApiToken($request));
    }

    public function testAuthenticateApiTokenRejectsCookieOnlyRequest() : void
    {
        $_COOKIE['id'] = self::TOKEN;
        $request = $this->createMock(Request::class);
        $request->expects(self::exactly(2))->method('getHeader')->willReturn(null);
        $this->personalApiTokenServiceMock->expects(self::never())->method('findUserIdByToken');
        $this->userRepositoryMock->expects(self::never())->method('findAuthTokenData');

        self::assertNull($this->subject->authenticateApiToken($request));
        self::assertNull($this->subject->authenticateApiToken($request));
    }

    public function testRequireApiTokenReturnsAuthenticatedUser() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getHeader')->willReturnMap([
            ['Authorization', 'Bearer ' . self::TOKEN],
            ['X-Movary-Token', null],
        ]);
        $this->personalApiTokenServiceMock
            ->expects(self::once())
            ->method('findUserIdByToken')
            ->with(self::TOKEN)
            ->willReturn(12);

        $result = $this->subject->requireApiToken($request);

        self::assertSame(12, $result->getUserId());
        self::assertSame(CredentialType::API_TOKEN, $result->getCredentialType());
    }

    public function testRequireApiTokenRejectsInvalidToken() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getHeader')->willReturnMap([
            ['Authorization', 'Bearer ' . self::TOKEN],
            ['X-Movary-Token', null],
        ]);
        $this->personalApiTokenServiceMock
            ->expects(self::once())
            ->method('findUserIdByToken')
            ->with(self::TOKEN)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not find a valid API token');

        $this->subject->requireApiToken($request);
    }

    public function testAuthenticateWebSessionReturnsAuthenticatedUser() : void
    {
        $_COOKIE['id'] = self::TOKEN;
        $this->userRepositoryMock
            ->expects(self::once())
            ->method('findAuthTokenData')
            ->with(hash('sha256', self::TOKEN))
            ->willReturn([
                'userId' => 12,
                'expirationDate' => DateTime::createFromString('+1 hour'),
            ]);
        $this->personalApiTokenServiceMock->expects(self::never())->method('findUserIdByToken');

        $result = $this->subject->authenticateWebSession();

        self::assertSame(12, $result?->getUserId());
        self::assertSame(CredentialType::WEB_SESSION, $result?->getCredentialType());
        self::assertSame($result, $this->subject->authenticateWebSession());
    }

    public function testAuthenticateWebSessionRejectsExpiredTokenAndClearsCookie() : void
    {
        $_COOKIE['id'] = self::TOKEN;
        $this->userRepositoryMock
            ->expects(self::once())
            ->method('findAuthTokenData')
            ->with(hash('sha256', self::TOKEN))
            ->willReturn([
                'userId' => 12,
                'expirationDate' => DateTime::createFromString('-1 hour'),
            ]);
        $this->userRepositoryMock
            ->expects(self::once())
            ->method('deleteAuthToken')
            ->with(hash('sha256', self::TOKEN));
        $this->cookieSecurityMock->expects(self::once())->method('isSecure')->willReturn(true);

        self::assertNull($this->subject->authenticateWebSession());
        self::assertArrayNotHasKey('id', $_COOKIE);
        self::assertNull($this->subject->authenticateWebSession());
    }

    public function testAuthenticateWebSessionRejectsMissingCookie() : void
    {
        $this->userRepositoryMock->expects(self::never())->method('findAuthTokenData');

        self::assertNull($this->subject->authenticateWebSession());
        self::assertNull($this->subject->authenticateWebSession());
    }

    public function testRequireWebSessionRejectsMissingCookie() : void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not find an authenticated web session');

        $this->subject->requireWebSession();
    }

    public function testLogoutClearsPendingFlashMessagesWithoutAuthenticationCookie() : void
    {
        unset($_COOKIE['id']);
        $this->userRepositoryMock->expects(self::never())->method('deleteAuthToken');
        $this->flashMessageServiceMock->expects(self::once())->method('clear');

        $this->subject->logout();
    }

    public function testLogoutInvalidatesCachedWebSession() : void
    {
        $_COOKIE['id'] = self::TOKEN;
        $this->userRepositoryMock
            ->expects(self::once())
            ->method('findAuthTokenData')
            ->with(hash('sha256', self::TOKEN))
            ->willReturn([
                'userId' => 12,
                'expirationDate' => DateTime::createFromString('+1 hour'),
            ]);
        $this->userRepositoryMock
            ->expects(self::once())
            ->method('deleteAuthToken')
            ->with(hash('sha256', self::TOKEN));
        $this->cookieSecurityMock->expects(self::once())->method('isSecure')->willReturn(true);
        $this->flashMessageServiceMock->expects(self::once())->method('clear');

        self::assertSame(12, $this->subject->requireWebSession()->getUserId());
        $this->subject->logout();

        self::assertNull($this->subject->authenticateWebSession());
    }

    public function testLoginWebSessionStoresHashedTokenAndSetsCookie() : void
    {
        $user = $this->createMock(\Movary\Domain\User\UserEntity::class);
        $user->method('getId')->willReturn(12);
        $storedToken = null;
        $this->userRepositoryMock->method('findUserByEmail')->willReturn($user);
        $this->userApiMock->method('isValidPassword')->with(12, 'password')->willReturn(true);
        $this->userApiMock->method('findTotpUri')->with(12)->willReturn(null);
        $this->userRepositoryMock
            ->expects(self::once())
            ->method('createAuthToken')
            ->willReturnCallback(static function (
                int $userId,
                string $tokenHash,
                string $deviceName,
                string $userAgent,
                DateTime $expirationDate,
            ) use (&$storedToken) : void {
                self::assertSame(12, $userId);
                self::assertSame('Movary Web', $deviceName);
                self::assertSame('agent', $userAgent);
                self::assertInstanceOf(DateTime::class, $expirationDate);
                $storedToken = $tokenHash;
            });

        $this->subject->loginWebSession('user@example.com', 'password', false, 'agent');

        self::assertArrayHasKey('id', $_COOKIE);
        self::assertSame(hash('sha256', $_COOKIE['id']), $storedToken);
        self::assertSame(12, $this->subject->requireWebSession()->getUserId());
    }

    public function testLoginWebSessionUsesRememberMeExpiration() : void
    {
        $user = $this->createStub(\Movary\Domain\User\UserEntity::class);
        $user->method('getId')->willReturn(12);
        $this->userRepositoryMock->method('findUserByEmail')->willReturn($user);
        $this->userApiMock->method('isValidPassword')->willReturn(true);
        $this->userApiMock->method('findTotpUri')->willReturn(null);
        $this->userRepositoryMock
            ->expects(self::once())
            ->method('createAuthToken')
            ->willReturnCallback(static function (
                int $userId,
                string $tokenHash,
                string $deviceName,
                string $userAgent,
                DateTime $expirationDate,
            ) : void {
                self::assertSame(12, $userId);
                self::assertNotSame('', $tokenHash);
                self::assertSame('Movary Web', $deviceName);
                self::assertSame('agent', $userAgent);
                self::assertSame(
                    DateTime::createFromString('+30 days')->format('Y-m-d'),
                    $expirationDate->format('Y-m-d'),
                );
            });

        $this->subject->loginWebSession('user@example.com', 'password', true, 'agent');
    }

    public function testFailedPasswordIsRecordedForRateLimiting() : void
    {
        $user = $this->createMock(\Movary\Domain\User\UserEntity::class);
        $user->method('getId')->willReturn(12);
        $this->userRepositoryMock->method('findUserByEmail')->with('user@example.com')->willReturn($user);
        $this->userApiMock->method('isValidPassword')->with(12, 'wrong-password')->willReturn(false);
        $this->loginAttemptLimiterMock
            ->expects(self::once())
            ->method('reserveAttempt')
            ->with('user@example.com')
            ->willReturn(1);
        $this->loginAttemptLimiterMock
            ->expects(self::never())
            ->method('releaseAttempt');

        $this->expectException(InvalidPassword::class);

        $this->subject->loginWebSession('user@example.com', 'wrong-password', false, 'agent');
    }

    public function testSuccessfulLoginResetsAccountRateLimit() : void
    {
        $user = $this->createMock(\Movary\Domain\User\UserEntity::class);
        $user->method('getId')->willReturn(12);
        $this->userRepositoryMock->method('findUserByEmail')->with('user@example.com')->willReturn($user);
        $this->userApiMock->method('isValidPassword')->with(12, 'password')->willReturn(true);
        $this->userApiMock->method('findTotpUri')->with(12)->willReturn(null);
        $this->loginAttemptLimiterMock
            ->expects(self::once())
            ->method('reserveAttempt')
            ->with('user@example.com')
            ->willReturn(1);
        $this->loginAttemptLimiterMock
            ->expects(self::once())
            ->method('resetAccountAttempts')
            ->with('user@example.com');

        $this->subject->loginWebSession('user@example.com', 'password', false, 'agent');

        self::assertSame(12, $this->subject->requireWebSession()->getUserId());
    }

    public function testMissingTotpCodeReleasesRateLimitReservation() : void
    {
        $user = $this->createMock(\Movary\Domain\User\UserEntity::class);
        $user->method('getId')->willReturn(12);
        $this->userRepositoryMock->method('findUserByEmail')->with('user@example.com')->willReturn($user);
        $this->userApiMock->method('isValidPassword')->with(12, 'password')->willReturn(true);
        $this->userApiMock->method('findTotpUri')->with(12)->willReturn('otpauth://totp/example');
        $this->loginAttemptLimiterMock->expects(self::once())->method('reserveAttempt')->willReturn(1);
        $this->loginAttemptLimiterMock->expects(self::once())->method('releaseAttempt')->with(1);

        $this->expectException(\Movary\Domain\User\Exception\MissingTotpCode::class);

        $this->subject->loginWebSession('user@example.com', 'password', false, 'agent');
    }
}
