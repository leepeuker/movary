<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\PlayedRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PlayedRequestMapper::class)]
class PlayedRequestMapperTest extends TestCase
{
    private PlayedRequestMapper $subject;

    protected function setUp() : void
    {
        $movieApi = $this->createStub(MovieApi::class);
        $movieApi->method('findById')->willReturn($this->createStub(MovieEntity::class));

        $this->subject = new PlayedRequestMapper(new RequestMapper(
            $this->createStub(UserApi::class),
            $movieApi,
        ));
    }

    public function testMapsAdditionDefaultsAndNullableDates() : void
    {
        $result = $this->subject->mapAdditions($this->createRequest(
            '[{"movaryId":42,"watchDates":['
            . '{"watchedAt":null,"plays":null},'
            . '{"watchedAt":"2026-10-20","plays":2,"comment":"A comment"}'
            . ']}]',
        ));

        self::assertSame(42, $result[0]['movieId']);
        self::assertNull($result[0]['watchDates'][0]['watchedAt']);
        self::assertSame(1, $result[0]['watchDates'][0]['plays']);
        self::assertNull($result[0]['watchDates'][0]['comment']);
        self::assertSame('2026-10-20', (string)$result[0]['watchDates'][1]['watchedAt']);
        self::assertSame(2, $result[0]['watchDates'][1]['plays']);
        self::assertSame('A comment', $result[0]['watchDates'][1]['comment']);
    }

    public function testMapsNullableDeletionDates() : void
    {
        $result = $this->subject->mapDeletions($this->createRequest(
            '[{"movaryId":42,"watchDates":[null,"2026-10-20"]}]',
        ));

        self::assertNull($result[0]['watchDates'][0]);
        self::assertSame('2026-10-20', (string)$result[0]['watchDates'][1]);
    }

    public function testRejectsUpdateWithoutPlays() : void
    {
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('items[0].watchDates[0].plays', 'must be a positive integer'),
        );

        $this->subject->mapUpdates($this->createRequest(
            '[{"movaryId":42,"watchDates":[{"watchedAt":"2026-10-20"}]}]',
        ));
    }

    public function testRejectsNonObjectWatchDate() : void
    {
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('items[0].watchDates[0]', 'must be an object'),
        );

        $this->subject->mapAdditions($this->createRequest(
            '[{"movaryId":42,"watchDates":["2026-10-20"]}]',
        ));
    }

    private function createRequest(string $body) : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn($body);

        return $request;
    }
}
