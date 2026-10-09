<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Movary\Service\CookieSecurity;
use Movary\Service\ServerSettings;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CookieSecurity::class)]
#[AllowMockObjectsWithoutExpectations]
class CookieSecurityTest extends TestCase
{
    public function testCookieIsSecureForHttpsRequest() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('isHttps')->willReturn(true);
        $serverSettings = $this->createMock(ServerSettings::class);
        $serverSettings->expects(self::never())->method('getApplicationUrl');

        self::assertTrue((new CookieSecurity($request, $serverSettings))->isSecure());
    }

    public function testCookieIsSecureForHttpsApplicationUrl() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('isHttps')->willReturn(false);
        $serverSettings = $this->createMock(ServerSettings::class);
        $serverSettings->method('getApplicationUrl')->willReturn('https://movary.example.com');

        self::assertTrue((new CookieSecurity($request, $serverSettings))->isSecure());
    }

    public function testCookieIsNotSecureForHttpApplicationUrl() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('isHttps')->willReturn(false);
        $serverSettings = $this->createMock(ServerSettings::class);
        $serverSettings->method('getApplicationUrl')->willReturn('http://movary.example.com');

        self::assertFalse((new CookieSecurity($request, $serverSettings))->isSecure());
    }
}
