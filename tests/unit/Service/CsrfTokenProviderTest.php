<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Movary\Domain\User\Service\Authentication;
use Movary\Service\CsrfTokenProvider;
use Movary\Service\CsrfTokenService;
use Movary\Util\Cookie;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Service\CsrfTokenProvider::class)]
class CsrfTokenProviderTest extends TestCase
{
    public function testReusesValidCookieToken() : void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects(self::exactly(2))
            ->method('getCookie')
            ->willReturnMap([
                [Authentication::AUTHENTICATION_COOKIE_NAME, 'authentication-token'],
                [CsrfTokenProvider::COOKIE_NAME, 'csrf-token'],
            ]);

        $tokenService = $this->createMock(CsrfTokenService::class);
        $tokenService
            ->expects(self::once())
            ->method('isValid')
            ->with('csrf-token', 'csrf-token', 'authentication-token')
            ->willReturn(true);
        $tokenService->expects(self::never())->method('create');

        $cookie = $this->createMock(Cookie::class);
        $cookie->expects(self::never())->method('set');

        $subject = new CsrfTokenProvider(
            $tokenService,
            $request,
            $cookie,
        );

        self::assertSame('csrf-token', $subject->getToken());
        self::assertSame('csrf-token', $subject->getToken());
    }

    public function testReplacesInvalidCookieToken() : void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects(self::exactly(2))
            ->method('getCookie')
            ->willReturnMap([
                [Authentication::AUTHENTICATION_COOKIE_NAME, 'authentication-token'],
                [CsrfTokenProvider::COOKIE_NAME, 'invalid-csrf-token'],
            ]);
        $tokenService = $this->createMock(CsrfTokenService::class);
        $tokenService
            ->expects(self::once())
            ->method('isValid')
            ->with('invalid-csrf-token', 'invalid-csrf-token', 'authentication-token')
            ->willReturn(false);
        $tokenService
            ->expects(self::once())
            ->method('create')
            ->with('authentication-token')
            ->willReturn('new-csrf-token');

        $cookie = $this->createMock(Cookie::class);
        $cookie
            ->expects(self::once())
            ->method('set')
            ->with(CsrfTokenProvider::COOKIE_NAME, 'new-csrf-token', null);

        $subject = new CsrfTokenProvider(
            $tokenService,
            $request,
            $cookie,
        );

        self::assertSame('new-csrf-token', $subject->getToken());
    }
}
