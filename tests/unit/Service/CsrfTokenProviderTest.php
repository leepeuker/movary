<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Movary\Domain\User\Service\Authentication;
use Movary\Service\CsrfTokenProvider;
use Movary\Service\CsrfTokenService;
use Movary\Service\ServerSettings;
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

        $serverSettings = $this->createMock(ServerSettings::class);
        $serverSettings->expects(self::never())->method('getApplicationUrl');

        $subject = new CsrfTokenProvider(
            $tokenService,
            $request,
            $serverSettings,
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
        $request->expects(self::once())->method('isHttps')->willReturn(true);

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

        $serverSettings = $this->createMock(ServerSettings::class);
        $serverSettings->expects(self::never())->method('getApplicationUrl');

        $subject = new CsrfTokenProvider(
            $tokenService,
            $request,
            $serverSettings,
        );

        self::assertSame('new-csrf-token', $subject->getToken());
    }
}
