<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Movie\History\Location\MovieHistoryLocationApi;
use Movary\Domain\Movie\History\Location\MovieHistoryLocationEntity;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\PlayRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PlayRequestMapper::class)]
class PlayRequestMapperTest extends TestCase
{
    public function testMapsCompleteUpdateWithUnknownDate() : void
    {
        $locationApi = $this->createStub(MovieHistoryLocationApi::class);
        $subject = $this->createSubject($locationApi);
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn('{"watchedAt":null,"position":2,"comment":null,"locationId":null}');

        self::assertSame([
            'watchedAt' => null,
            'position' => 2,
            'comment' => null,
            'locationId' => null,
        ], $subject->mapUpdate($request, 7));
    }

    public function testRejectsAnotherUsersLocation() : void
    {
        $location = MovieHistoryLocationEntity::createFromArray([
            'id' => 8,
            'user_id' => 9,
            'name' => 'Other location',
            'is_cinema' => 0,
        ]);
        $locationApi = $this->createStub(MovieHistoryLocationApi::class);
        $locationApi->method('findLocationById')->willReturn($location);
        $subject = $this->createSubject($locationApi);
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn('{"watchedAt":"2026-10-29","position":2,"comment":"test","locationId":8}');

        $this->expectException(InvalidApiRequest::class);
        $subject->mapUpdate($request, 7);
    }

    private function createSubject(MovieHistoryLocationApi $locationApi) : PlayRequestMapper
    {
        return new PlayRequestMapper(
            new RequestMapper($this->createStub(UserApi::class), $this->createStub(MovieApi::class)),
            $locationApi,
        );
    }
}
