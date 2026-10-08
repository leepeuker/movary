<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject\Http;

use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(\Movary\ValueObject\Http\Request::class)]
class RequestTest extends TestCase
{
    public function testReturnsStringCookie() : void
    {
        $subject = $this->createRequest(cookies: ['csrf' => 'token', 'invalid' => ['value']]);

        self::assertSame('token', $subject->getCookie('csrf'));
        self::assertNull($subject->getCookie('invalid'));
        self::assertNull($subject->getCookie('missing'));
    }

    public function testReturnsHeaderCaseInsensitivelyAndPreservesHeaderCollection() : void
    {
        $headers = ['x-CsRf-ToKeN' => 'token', 'Invalid' => ['value']];
        $subject = $this->createRequest(headers: $headers);

        self::assertSame('token', $subject->getHeader('X-CSRF-TOKEN'));
        self::assertNull($subject->getHeader('invalid'));
        self::assertNull($subject->getHeader('missing'));
        self::assertSame($headers, $subject->getHeaders());
    }

    private function createRequest(array $headers = [], array $cookies = []) : Request
    {
        $reflection = new ReflectionClass(Request::class);
        $request = $reflection->newInstanceWithoutConstructor();
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        $constructor->invoke(
            $request,
            '/',
            [],
            [],
            '',
            [],
            $headers,
            $cookies,
            '',
            null,
            null,
            false,
        );

        return $request;
    }
}
