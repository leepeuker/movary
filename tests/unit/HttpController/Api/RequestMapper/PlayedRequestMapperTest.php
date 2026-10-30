<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\PlayedRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PlayedRequestMapper::class)]
class PlayedRequestMapperTest extends TestCase
{
    public function testMapsMovieReadDefaults() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'Owner']);
        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(12);
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('fetchUserByName')->willReturn($user);
        $subject = new PlayedRequestMapper(new RequestMapper($userApi, $this->createStub(MovieApi::class)));

        $result = $subject->mapRequest($request);

        self::assertSame(12, $result->getRequestedUserId());
        self::assertSame(1, $result->getPage());
        self::assertSame(24, $result->getLimit());
        self::assertSame('title', $result->getSortBy());
        self::assertSame('asc', (string)$result->getSortOrder());
    }

    public function testRejectsUnsupportedMovieSort() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'Owner']);
        $request->method('getGetParameters')->willReturn(['sortBy' => 'position']);
        $userApi = $this->createStub(UserApi::class);
        $userApi->method('fetchUserByName')->willReturn($this->createStub(UserEntity::class));
        $subject = new PlayedRequestMapper(new RequestMapper($userApi, $this->createStub(MovieApi::class)));

        $this->expectException(InvalidApiRequest::class);
        $subject->mapRequest($request);
    }
}
