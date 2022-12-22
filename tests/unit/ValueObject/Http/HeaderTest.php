<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject\Http;

use Movary\ValueObject\Http\Header;
use PHPUnit\Framework\TestCase;

/** @covers \Movary\ValueObject\Http\Header */
class HeaderTest extends TestCase
{
    public function testCreateContentTypeCsv() : void
    {
        self::assertSame('Content-Type: text/csv', (string)Header::createContentTypeCsv());
    }

    public function testCreateContentTypeJson() : void
    {
        self::assertSame('Content-Type: application/json', (string)Header::createContentTypeJson());
    }

    public function testCreateLocation() : void
    {
        self::assertSame('Location: foobar', (string)Header::createLocation('foobar'));
    }
}
