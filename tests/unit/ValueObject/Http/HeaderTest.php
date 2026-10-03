<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject\Http;

use InvalidArgumentException;
use Movary\ValueObject\Http\Header;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\ValueObject\Http\Header::class)]
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

    public function testCreateContentTypeZip() : void
    {
        self::assertSame('Content-Type: application/zip', (string)Header::createContentTypeZip());
    }

    public function testCreateAttachment() : void
    {
        self::assertSame(
            'Content-Disposition: attachment; filename="export.zip"',
            (string)Header::createAttachment('export.zip'),
        );
    }

    public function testCreateAttachmentRejectsInvalidFilename() : void
    {
        $this->expectException(InvalidArgumentException::class);

        Header::createAttachment("export.zip\r\nX-Injected: true");
    }

    public function testCreateRetryAfter() : void
    {
        self::assertSame('Retry-After: 60', (string)Header::createRetryAfter(60));
    }

    public function testCreateLocation() : void
    {
        self::assertSame('Location: foobar', (string)Header::createLocation('foobar'));
    }
}
