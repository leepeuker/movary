<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\ResponseMapper;

use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\ResponseMapper\ErrorResponseMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ErrorResponseMapper::class)]
class ErrorResponseMapperTest extends TestCase
{
    private ErrorResponseMapper $subject;

    protected function setUp() : void
    {
        $this->subject = new ErrorResponseMapper();
    }

    public function testMapsInvalidRequest() : void
    {
        $response = $this->subject->mapInvalidRequest(
            InvalidApiRequest::invalidField('items[0].movaryId', 'must be a positive integer'),
        );

        self::assertSame(400, $response->getStatusCode()->getCode());
        self::assertSame(
            '{"error":"InvalidRequest","message":"Field \\"items[0].movaryId\\" must be a positive integer."}',
            $response->getBody(),
        );
        self::assertSame(
            ['Content-Type: application/json', 'Cache-Control: private, no-cache'],
            array_map('strval', $response->getHeaders()),
        );
    }

    public function testMapsInternalServerErrorWithoutExceptionDetails() : void
    {
        $response = $this->subject->mapInternalServerError();

        self::assertSame(500, $response->getStatusCode()->getCode());
        self::assertSame(
            '{"error":"InternalServerError","message":"The request could not be processed."}',
            $response->getBody(),
        );
        self::assertSame(
            ['Content-Type: application/json', 'Cache-Control: private, no-cache'],
            array_map('strval', $response->getHeaders()),
        );
    }
}
