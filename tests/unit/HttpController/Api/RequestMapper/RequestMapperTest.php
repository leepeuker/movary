<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestMapper::class)]
class RequestMapperTest extends TestCase
{
    private RequestMapper $subject;

    protected function setUp() : void
    {
        $this->subject = new RequestMapper(
            $this->createStub(UserApi::class),
            $this->createStub(MovieApi::class),
        );
    }

    public function testMapsJsonObjectList() : void
    {
        self::assertSame(
            [[
                'movaryId' => 42,
                'watchDates' => [[
                    'watchedAt' => '2026-10-20',
                    'metadata' => ['source' => 'api'],
                ]],
            ]],
            $this->subject->mapJsonObjectList($this->createRequest(
                '[{"movaryId":42,"watchDates":[{"watchedAt":"2026-10-20","metadata":{"source":"api"}}]}]',
            )),
        );
    }

    public function testRejectsMalformedJson() : void
    {
        $this->expectExceptionObject(InvalidApiRequest::invalidJson());

        $this->subject->mapJsonObjectList($this->createRequest('{'));
    }

    public function testRejectsTopLevelObject() : void
    {
        $this->expectExceptionObject(InvalidApiRequest::invalidJson());

        $this->subject->mapJsonObjectList($this->createRequest('{"movaryId":42}'));
    }

    public function testMapsExistingMovieId() : void
    {
        $movieApi = $this->createMock(MovieApi::class);
        $movieApi
            ->expects(self::once())
            ->method('findById')
            ->with(42)
            ->willReturn($this->createStub(MovieEntity::class));
        $subject = new RequestMapper($this->createStub(UserApi::class), $movieApi);

        self::assertSame(42, $subject->mapExistingMovieId(['movaryId' => 42], 'items[0]'));
    }

    public function testRejectsUnknownMovieId() : void
    {
        $movieApi = $this->createMock(MovieApi::class);
        $movieApi
            ->expects(self::once())
            ->method('findById')
            ->with(42)
            ->willReturn(null);
        $subject = new RequestMapper($this->createStub(UserApi::class), $movieApi);
        $this->expectExceptionObject(InvalidApiRequest::movieNotFound(42));

        $subject->mapExistingMovieId(['movaryId' => 42], 'items[0]');
    }

    public function testRejectsNonCanonicalDate() : void
    {
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('items[0].watchedAt', 'must be a date in YYYY-MM-DD format'),
        );

        $this->subject->mapRequiredDate(
            ['watchedAt' => '2026-2-3'],
            'watchedAt',
            'items[0].watchedAt',
        );
    }

    public function testRejectsPositiveIntegerAboveMaximum() : void
    {
        $this->expectExceptionObject(InvalidApiRequest::invalidField('items[0].plays', 'must be at most 32767'));

        $this->subject->mapRequiredPositiveInteger(['plays' => 32768], 'plays', 'items[0].plays', 32767);
    }

    public function testMapsOptionalNullIntegerToDefault() : void
    {
        self::assertSame(
            1,
            $this->subject->mapOptionalPositiveInteger(['position' => null], 'position', 'items[0].position', 1),
        );
    }

    private function createRequest(string $body) : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn($body);

        return $request;
    }
}
