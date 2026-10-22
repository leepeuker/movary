<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Mapper;

use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Web\Mapper\MoviesRequestMapper;
use Movary\Service\ListPagePreference;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MoviesRequestMapper::class)]
class MoviesRequestMapperTest extends TestCase
{
    public function testUsesApplicationDefaultWithoutSavedSorting() : void
    {
        $result = $this->createSubject()->mapRenderPageRequest($this->createRequest([], null));

        self::assertSame('title', $result->getSortBy());
        self::assertSame('asc', (string)$result->getSortOrder());
        self::assertSame(24, $result->getLimit());
    }

    public function testSavedPreferenceOverridesApplicationDefault() : void
    {
        $request = $this->createRequest(
            [],
            '{"version":1,"pages":{"movies":{"sortBy":"rating","sortOrder":"desc",'
            . '"filters":{"ge":"Drama","la":"en","ur":"1","minur":"7","maxur":"9","loc":"4"},'
            . '"perPage":"48"}}}',
        );

        $result = $this->createSubject()->mapRenderPageRequest($request);

        self::assertSame('rating', $result->getSortBy());
        self::assertSame('desc', (string)$result->getSortOrder());
        self::assertSame('Drama', $result->getGenre());
        self::assertSame('en', $result->getLanguage());
        self::assertTrue($result->hasUserRating());
        self::assertSame(7, $result->getUserRatingMin());
        self::assertSame(9, $result->getUserRatingMax());
        self::assertSame(4, $result->getLocationId());
        self::assertSame(48, $result->getLimit());
    }

    public function testQuerySortingOverridesSavedSorting() : void
    {
        $request = $this->createRequest(
            ['sb' => 'releaseDate', 'so' => 'asc', 'pp' => '72'],
            '{"version":1,"pages":{"movies":{"sortBy":"rating","sortOrder":"desc",'
            . '"filters":{"ge":"Drama"},"perPage":"48"}}}',
        );

        $result = $this->createSubject()->mapRenderPageRequest($request);

        self::assertSame('releaseDate', $result->getSortBy());
        self::assertSame('asc', (string)$result->getSortOrder());
        self::assertSame(72, $result->getLimit());
    }

    public function testExplicitEmptyFilterOverridesSavedFilter() : void
    {
        $request = $this->createRequest(
            ['ge' => '', 'ur' => '', 'loc' => ''],
            '{"version":1,"pages":{"movies":{"sortBy":"title","sortOrder":"asc",'
            . '"filters":{"ge":"Drama","ur":"1","loc":"4"}}}}',
        );

        $result = $this->createSubject()->mapRenderPageRequest($request);

        self::assertNull($result->getGenre());
        self::assertNull($result->hasUserRating());
        self::assertNull($result->getLocationId());
    }

    private function createRequest(array $getParameters, ?string $cookie) : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'viewer']);
        $request->method('getGetParameters')->willReturn($getParameters);
        $request->method('getCookie')->willReturn($cookie);

        return $request;
    }

    private function createSubject() : MoviesRequestMapper
    {
        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(42);

        $userApi = $this->createStub(UserApi::class);
        $userApi->method('fetchUserByName')->willReturn($user);

        return new MoviesRequestMapper($userApi, new ListPagePreference());
    }
}
