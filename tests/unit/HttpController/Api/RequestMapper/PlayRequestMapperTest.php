<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Movie\History\Location\MovieHistoryLocationApi;
use Movary\Domain\Movie\History\Location\MovieHistoryLocationEntity;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
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
    public function testMapsSinglePlayCreationWithUnknownDate() : void
    {
        $subject = $this->createSubject($this->createStub(MovieHistoryLocationApi::class));
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn('{"movaryId":42,"watchedAt":null,"comment":"A play"}');

        self::assertSame([
            'movieId' => 42,
            'watchedAt' => null,
            'position' => null,
            'comment' => 'A play',
            'locationId' => null,
        ], $subject->mapCreation($request, 7));
    }

    public function testRejectsAggregatePlayCountOnCreation() : void
    {
        $subject = $this->createSubject($this->createStub(MovieHistoryLocationApi::class));
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn('{"movaryId":42,"watchedAt":null,"plays":2}');

        $this->expectException(InvalidApiRequest::class);
        $subject->mapCreation($request, 7);
    }

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
        $movieApi = $this->createStub(MovieApi::class);
        $movieApi->method('findById')->willReturn($this->createStub(MovieEntity::class));

        return new PlayRequestMapper(
            new RequestMapper($this->createStub(UserApi::class), $movieApi),
            $locationApi,
        );
    }
}
