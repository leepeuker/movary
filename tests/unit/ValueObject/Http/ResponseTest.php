<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject\Http;

use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\ValueObject\Http\Response::class)]
class ResponseTest extends TestCase
{
    public function testCreateBadRequest() : void
    {
        self::assertEquals(
            Response::create(StatusCode::createBadRequest()),
            Response::createBadRequest(),
        );
    }

    public function testCreateCsv() : void
    {
        self::assertEquals(
            Response::create(StatusCode::createOk(), 'foobar', [Header::createContentTypeCsv()]),
            Response::createCsv('foobar'),
        );
    }

    public function testCreateForbidden() : void
    {
        self::assertEquals(
            Response::create(StatusCode::createForbidden()),
            Response::createForbidden(),
        );
    }

    public function testCreateForbiddenRedirectWithoutApplicationUrl() : void
    {
        self::assertEquals(
            Response::create(
                StatusCode::createForbidden(),
                null,
                [Header::createLocation('/login?redirect=%2Fusers%2Falice')],
            ),
            Response::createForbiddenRedirect('/users/alice', '/'),
        );
    }

    public function testCreateForbiddenRedirectWithApplicationUrl() : void
    {
        self::assertEquals(
            Response::create(
                StatusCode::createForbidden(),
                null,
                [Header::createLocation('https://example.com/movary/login?redirect=%2Fusers%2Falice')],
            ),
            Response::createForbiddenRedirect('/users/alice', 'https://example.com/movary'),
        );
    }

    public function testCreateJson() : void
    {
        self::assertEquals(
            Response::create(StatusCode::createOk(), 'foobar', [Header::createContentTypeJson()]),
            Response::createJson('foobar'),
        );
    }

    public function testCreateZipDownload() : void
    {
        $subject = Response::createZipDownload('zip content', 'export.zip');

        self::assertEquals(
            [
                Header::createContentTypeZip(),
                Header::createAttachment('export.zip'),
                Header::createCacheControlPrivate(),
            ],
            $subject->getHeaders(),
        );
        self::assertSame(StatusCode::createOk()->getCode(), $subject->getStatusCode()->getCode());
        self::assertSame('zip content', $subject->getBody());
    }

    public function testCreateNotFound() : void
    {
        self::assertEquals(
            Response::create(StatusCode::createNotFound()),
            Response::createNotFound(),
        );
    }

    public function testCreateOk() : void
    {
        self::assertEquals(
            Response::create(StatusCode::createOk()),
            Response::createOk(),
        );
    }

    public function testCreateSeeOther() : void
    {
        self::assertEquals(
            Response::create(StatusCode::createSeeOther(), null, [Header::createLocation('/foobar')]),
            Response::createSeeOther('/foobar'),
        );
    }

    public function testGetters() : void
    {
        $subject = Response::create(StatusCode::createOk(), 'foobar', [Header::createContentTypeJson()]);

        self::assertSame($subject->getBody(), 'foobar');
        self::assertEquals($subject->getStatusCode(), StatusCode::createOk());
        self::assertEquals(
            $subject->getHeaders(),
            [Header::createContentTypeJson(), Header::createCacheControlPrivate()],
        );
    }

    public function testExplicitCacheHeaderOverridesDefault() : void
    {
        $subject = Response::create(
            StatusCode::createOk(),
            'foobar',
            [Header::createCache(3600)],
        );

        self::assertEquals([Header::createCache(3600)], $subject->getHeaders());
    }
}
