<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Util;

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
        $subject = new Cookie(static function (string $name, string $value, array $cookieOptions) use (&$options) : void {
            self::assertSame('test', $name);
            self::assertSame('value', $value);
            $options = $cookieOptions;
        });

        $subject->set('test', 'value', 1300, true);

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
        $subject = new Cookie(static function (string $name, string $value, array $cookieOptions) use (&$options) : void {
            self::assertSame('test', $name);
            self::assertSame('', $value);
            $options = $cookieOptions;
        });

        $subject->delete('test', false);

        self::assertArrayNotHasKey('test', $_COOKIE);
        self::assertSame(1, $options['expires']);
        self::assertFalse($options['secure']);
    }

    public function testSetWithoutExpirationCreatesSessionCookie() : void
    {
        $options = [];
        $subject = new Cookie(static function (string $name, string $value, array $cookieOptions) use (&$options) : void {
            self::assertSame('test', $name);
            self::assertSame('value', $value);
            $options = $cookieOptions;
        });

        $subject->set('test', 'value', null, true);

        self::assertArrayNotHasKey('expires', $options);
    }

    public function testFindOnlyReturnsNonEmptyString() : void
    {
        $subject = new Cookie();
        self::assertNull($subject->find('test'));

        $_COOKIE['test'] = '';
        self::assertNull($subject->find('test'));

        $_COOKIE['test'] = 'value';
        self::assertSame('value', $subject->find('test'));
    }
}
