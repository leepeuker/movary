<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Util;

use Movary\Service\CookieSecurity;
use Movary\Util\Cookie;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Cookie::class)]
class CookieTest extends TestCase
{
    protected function tearDown() : void
    {
        unset($_COOKIE['test']);
    }

    public function testSetUsesSecureHttpOnlySameSiteCookie() : void
    {
        $options = [];
        $cookieSecurity = $this->createMock(CookieSecurity::class);
        $cookieSecurity->expects(self::once())->method('isSecure')->willReturn(true);
        $subject = new Cookie(
            $cookieSecurity,
            static function (string $name, string $value, array $cookieOptions) use (&$options) : void {
                self::assertSame('test', $name);
                self::assertSame('value', $value);
                $options = $cookieOptions;
            },
        );

        $subject->set('test', 'value', 1300);

        self::assertSame('value', $_COOKIE['test']);
        self::assertSame([
            'expires' => 1300,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => true,
        ], $options);
    }

    public function testDeleteExpiresCookieAndRemovesCurrentValue() : void
    {
        $_COOKIE['test'] = 'value';
        $options = [];
        $cookieSecurity = $this->createMock(CookieSecurity::class);
        $cookieSecurity->expects(self::once())->method('isSecure')->willReturn(false);
        $subject = new Cookie(
            $cookieSecurity,
            static function (string $name, string $value, array $cookieOptions) use (&$options) : void {
                self::assertSame('test', $name);
                self::assertSame('', $value);
                $options = $cookieOptions;
            },
        );

        $subject->delete('test');

        self::assertArrayNotHasKey('test', $_COOKIE);
        self::assertSame(1, $options['expires']);
        self::assertFalse($options['secure']);
    }

    public function testSetWithoutExpirationCreatesSessionCookie() : void
    {
        $options = [];
        $cookieSecurity = $this->createMock(CookieSecurity::class);
        $cookieSecurity->expects(self::once())->method('isSecure')->willReturn(true);
        $subject = new Cookie(
            $cookieSecurity,
            static function (string $name, string $value, array $cookieOptions) use (&$options) : void {
                self::assertSame('test', $name);
                self::assertSame('value', $value);
                $options = $cookieOptions;
            },
        );

        $subject->set('test', 'value', null);

        self::assertArrayNotHasKey('expires', $options);
    }

    public function testFindOnlyReturnsNonEmptyString() : void
    {
        $cookieSecurity = $this->createMock(CookieSecurity::class);
        $cookieSecurity->expects(self::never())->method('isSecure');
        $subject = new Cookie($cookieSecurity);
        self::assertNull($subject->find('test'));

        $_COOKIE['test'] = '';
        self::assertNull($subject->find('test'));

        $_COOKIE['test'] = 'value';
        self::assertSame('value', $subject->find('test'));
    }
}
