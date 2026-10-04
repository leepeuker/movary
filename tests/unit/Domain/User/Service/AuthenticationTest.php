<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\LoginAttemptLimiter;
use Movary\Domain\User\Exception\InvalidPassword;
use Movary\Domain\User\Service\TwoFactorAuthenticationApi;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserRepository;
use Movary\Service\ServerSettings;
use Movary\Util\SessionWrapper;
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

    private MockObject|SessionWrapper $sessionWrapperMock;

    private MockObject|ServerSettings $serverSettingsMock;

    private MockObject|Request $requestMock;

    private MockObject|LoginAttemptLimiter $loginAttemptLimiterMock;

    protected function setUp() : void
    {
        unset($_COOKIE['id']);

        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->sessionWrapperMock = $this->createMock(SessionWrapper::class);
        $this->serverSettingsMock = $this->createMock(ServerSettings::class);
        $this->requestMock = $this->createMock(Request::class);
        $this->loginAttemptLimiterMock = $this->createMock(LoginAttemptLimiter::class);
        $this->subject = new Authentication(
            $this->userRepositoryMock,
            $this->userApiMock,
            $this->sessionWrapperMock,
            $this->createMock(TwoFactorAuthenticationApi::class),
            $this->loginAttemptLimiterMock,
            $this->serverSettingsMock,
        );
    }

    protected function tearDown() : void
    {
        unset($_COOKIE['id']);
    }

    public function testGetCurrentUserIdUsesValidatedCookieToken() : void
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

        self::assertTrue($this->subject->isUserAuthenticatedWithCookie());
        self::assertSame(12, $this->subject->getCurrentUserId());
    }

    public function testGetUserIdByTokenReturnsApiTokenUser() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getHeaders')->willReturn(['X-Movary-Token' => self::TOKEN]);

        $this->userApiMock
            ->expects(self::once())
            ->method('findUserIdByApiToken')
            ->with(self::TOKEN)
            ->willReturn(12);
        $this->userRepositoryMock->expects(self::never())->method('findAuthTokenData');

        self::assertSame(12, $this->subject->getUserIdByToken($request));
    }

    public function testGetUserIdByTokenRejectsAndDeletesExpiredAuthenticationToken() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getHeaders')->willReturn(['X-Movary-Token' => self::TOKEN]);

        $this->userApiMock
            ->expects(self::once())
            ->method('findUserIdByApiToken')
            ->with(self::TOKEN)
            ->willReturn(null);
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

        self::assertNull($this->subject->getUserIdByToken($request));
    }

    public function testSetAuthenticationCookieMakesTokenAvailableInCurrentRequest() : void
    {
        $this->sessionWrapperMock->expects(self::once())->method('destroy');
        $this->sessionWrapperMock->expects(self::once())->method('start');

        $this->subject->setAuthenticationCookieAndNewSession(
            self::TOKEN,
            DateTime::createFromString('+1 hour'),
        );

        self::assertSame(self::TOKEN, $_COOKIE['id']);
    }

    public function testLoginStoresHashedAuthenticationToken() : void
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
                self::assertSame('api-client', $deviceName);
                self::assertSame('agent', $userAgent);
                self::assertInstanceOf(DateTime::class, $expirationDate);
                $storedToken = $tokenHash;
            });

        $result = $this->subject->login('user@example.com', 'password', false, 'api-client', 'agent');

        self::assertSame(hash('sha256', $result['token']), $storedToken);
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

        $this->subject->findUserAndVerifyAuthentication('user@example.com', 'wrong-password');
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

        self::assertSame($user, $this->subject->findUserAndVerifyAuthentication('user@example.com', 'password'));
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

        $this->subject->findUserAndVerifyAuthentication('user@example.com', 'password');
    }

    public function testAuthenticationCookieIsSecureForHttpsRequests() : void
    {
        $this->requestMock->expects(self::once())->method('isHttps')->willReturn(true);
        $this->serverSettingsMock->expects(self::never())->method('getApplicationUrl');

        self::assertTrue($this->subject->isAuthenticationCookieSecure());
    }

    public function testAuthenticationCookieIsSecureForHttpsApplicationUrl() : void
    {
        $this->serverSettingsMock
            ->expects(self::once())
            ->method('getApplicationUrl')
            ->willReturn('https://movary.example.com');

        self::assertTrue($this->subject->isAuthenticationCookieSecure());
    }

    public function testAuthenticationCookieIsNotSecureForHttpApplicationUrl() : void
    {
        $this->serverSettingsMock
            ->expects(self::once())
            ->method('getApplicationUrl')
            ->willReturn('http://movary.example.com');

        self::assertFalse($this->subject->isAuthenticationCookieSecure());
    }
}
