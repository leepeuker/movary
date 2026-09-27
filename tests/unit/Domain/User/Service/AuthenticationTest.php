<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\TwoFactorAuthenticationApi;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserRepository;
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

    protected function setUp() : void
    {
        unset($_COOKIE['id']);

        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->userApiMock = $this->createMock(UserApi::class);
        $this->subject = new Authentication(
            $this->userRepositoryMock,
            $this->userApiMock,
            $this->createMock(SessionWrapper::class),
            $this->createMock(TwoFactorAuthenticationApi::class),
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
            ->method('findAuthTokenExpirationDate')
            ->with(self::TOKEN)
            ->willReturn(DateTime::createFromString('+1 hour'));
        $this->userRepositoryMock
            ->expects(self::once())
            ->method('findUserIdByAuthToken')
            ->with(self::TOKEN)
            ->willReturn(12);

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
        $this->userRepositoryMock->expects(self::never())->method('findAuthTokenExpirationDate');

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
            ->method('findAuthTokenExpirationDate')
            ->with(self::TOKEN)
            ->willReturn(DateTime::createFromString('-1 hour'));
        $this->userRepositoryMock
            ->expects(self::once())
            ->method('deleteAuthToken')
            ->with(self::TOKEN);
        $this->userRepositoryMock->expects(self::never())->method('findUserIdByAuthToken');

        self::assertNull($this->subject->getUserIdByToken($request));
    }
}
