<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Mapper;

use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Web\Mapper\WatchlistRequestMapper;
use Movary\Service\ListPagePreference;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WatchlistRequestMapper::class)]
class WatchlistRequestMapperTest extends TestCase
{
    public function testUsesApplicationDefaultWithoutSavedSorting() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'viewer']);
        $request->method('getGetParameters')->willReturn([]);
        $request->method('getCookie')->willReturn(null);

        $result = $this->createSubject()->mapRenderPageRequest($request);

        self::assertSame('addedAt', $result->getSortBy());
        self::assertSame('desc', (string)$result->getSortOrder());
    }

    public function testSavedPreferenceOverridesApplicationDefault() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'viewer']);
        $request->method('getGetParameters')->willReturn([]);
        $request->method('getCookie')->willReturn(
            '{"version":1,"pages":{"watchlist":{"sortBy":"title","sortOrder":"asc",'
            . '"filters":{"ge":"Drama","la":"en","pc":"DE"}}}}',
        );

        $result = $this->createSubject()->mapRenderPageRequest($request);

        self::assertSame('title', $result->getSortBy());
        self::assertSame('asc', (string)$result->getSortOrder());
        self::assertSame('Drama', $result->getGenre());
        self::assertSame('en', $result->getLanguage());
        self::assertSame('DE', $result->getProductionCountryCode());
    }

    private function createSubject() : WatchlistRequestMapper
    {
        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(42);

        $userApi = $this->createStub(UserApi::class);
        $userApi->method('fetchUserByName')->willReturn($user);

        return new WatchlistRequestMapper($userApi, new ListPagePreference());
    }
}
