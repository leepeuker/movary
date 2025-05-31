<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject\Http;

use Movary\ValueObject\Http\StatusCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\ValueObject\Http\StatusCode::class)]
class StatusCodeTest extends TestCase
{
    public function testCreateBadRequest() : void
    {
        self::assertSame('HTTP/1.1 400 Bad Request', (string)StatusCode::createBadRequest());
    }

    public function testCreateMethodNotAllowed() : void
    {
        self::assertSame('HTTP/1.1 405 Method Not Allowed', (string)StatusCode::createMethodNotAllowed());
    }

    public function testCreateForbidden() : void
    {
        self::assertSame('HTTP/1.1 403 Forbidden', (string)StatusCode::createForbidden());
    }

    public function testCreateNoContent() : void
    {
        self::assertSame('HTTP/1.1 204 No Content', (string)StatusCode::createNoContent());
    }

    public function testCreateNotFound() : void
    {
        self::assertSame('HTTP/1.1 404 Not Found', (string)StatusCode::createNotFound());
    }

    public function testCreateOk() : void
    {
        self::assertSame('HTTP/1.1 200 OK', (string)StatusCode::createOk());
    }

    public function testCreateSeeOther() : void
    {
        self::assertSame('HTTP/1.1 303 See Other', (string)StatusCode::createSeeOther());
    }
}
