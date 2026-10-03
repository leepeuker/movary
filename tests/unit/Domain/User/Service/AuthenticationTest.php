<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Service\Authentication;
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

    protected function setUp() : void
    {
        unset($_COOKIE['id']);

        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->sessionWrapperMock = $this->createMock(SessionWrapper::class);
        $this->serverSettingsMock = $this->createMock(ServerSettings::class);
        $this->requestMock = $this->createMock(Request::class);
        $this->subject = new Authentication(
            $this->userRepositoryMock,
            $this->userApiMock,
            $this->sessionWrapperMock,
            $this->createMock(TwoFactorAuthenticationApi::class),
            $this->serverSettingsMock,
            $this->requestMock,
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
            ->with(self::TOKEN)
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
            ->with(self::TOKEN)
            ->willReturn([
                'userId' => 12,
                'expirationDate' => DateTime::createFromString('-1 hour'),
            ]);
        $this->userRepositoryMock
            ->expects(self::once())
            ->method('deleteAuthToken')
            ->with(self::TOKEN);

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
