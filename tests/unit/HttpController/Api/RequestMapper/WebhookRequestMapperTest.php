<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\WebhookRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WebhookRequestMapper::class)]
class WebhookRequestMapperTest extends TestCase
{
    private WebhookRequestMapper $subject;

    protected function setUp() : void
    {
        $this->subject = new WebhookRequestMapper();
    }

    public function testMapsJsonBodyWithContentTypeParameters() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getHeader')->willReturn('application/json; charset=utf-8');
        $request->method('getBody')->willReturn('{"event":"played"}');

        self::assertSame(['event' => 'played'], $this->subject->mapJsonBody($request));
    }

    public function testRejectsWrongJsonBodyContentType() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getHeader')->willReturn('text/plain');
        $this->expectExceptionObject(InvalidApiRequest::unsupportedWebhookMediaType('application/json'));

        $this->subject->mapJsonBody($request);
    }

    public function testMapsMultipartJsonField() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getHeader')->willReturn('multipart/form-data; boundary=test');
        $request->method('getPostParameters')->willReturn(['payload' => '{"event":"played"}']);

        self::assertSame(['event' => 'played'], $this->subject->mapMultipartJsonField($request, 'payload'));
    }

    public function testRejectsMissingJsonField() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn([]);
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('data', 'is required and must contain a JSON string'),
        );

        $this->subject->mapJsonField($request, 'data');
    }

    public function testRejectsMalformedJson() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn(['data' => '{']);
        $this->expectExceptionObject(InvalidApiRequest::invalidWebhookPayload('must contain valid JSON'));

        $this->subject->mapJsonField($request, 'data');
    }

    public function testRejectsJsonList() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn(['data' => '[]']);
        $this->expectExceptionObject(InvalidApiRequest::invalidWebhookPayload('must contain a JSON object'));

        $this->subject->mapJsonField($request, 'data');
    }

    public function testAcceptsEmptyJsonObject() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn(['data' => '{}']);

        self::assertSame([], $this->subject->mapJsonField($request, 'data'));
    }

    public function testRejectsOversizedJson() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn([
            'data' => str_repeat(' ', WebhookRequestMapper::MAXIMUM_JSON_BYTES + 1),
        ]);
        $this->expectExceptionObject(
            InvalidApiRequest::payloadTooLarge(WebhookRequestMapper::MAXIMUM_JSON_BYTES),
        );

        $this->subject->mapJsonField($request, 'data');
    }
}
