<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Mapper;

use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\HttpController\Web\Mapper\PersonsRequestMapper;
use Movary\Service\ListPagePreference;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PersonsRequestMapper::class)]
class PersonsRequestMapperTest extends TestCase
{
    public function testUsesApplicationDefaultWithoutSavedSorting() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'viewer']);
        $request->method('getGetParameters')->willReturn([]);
        $request->method('getCookie')->willReturn(null);

        $result = $this->createSubject()->mapRenderPageRequest($request, ListPagePreference::PAGE_ACTORS);

        self::assertSame('uniqueAppearances', $result->getSortBy());
        self::assertSame('desc', (string)$result->getSortOrder());
    }

    public function testUsesSeparatePreferencesForActorsAndDirectors() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['username' => 'viewer']);
        $request->method('getGetParameters')->willReturn([]);
        $request->method('getCookie')->willReturn(
            '{"version":1,"pages":{'
            . '"actors":{"sortBy":"name","sortOrder":"asc","filters":{"ge":"2"}},'
            . '"directors":{"sortBy":"totalAppearances","sortOrder":"desc","filters":{"ge":"1"}}'
            . '}}',
        );

        $subject = $this->createSubject();
        $actors = $subject->mapRenderPageRequest($request, ListPagePreference::PAGE_ACTORS);
        $directors = $subject->mapRenderPageRequest($request, ListPagePreference::PAGE_DIRECTORS);

        self::assertSame('name', $actors->getSortBy());
        self::assertSame('asc', (string)$actors->getSortOrder());
        self::assertSame(2, $actors->getGender()?->asInt());
        self::assertSame('totalAppearances', $directors->getSortBy());
        self::assertSame('desc', (string)$directors->getSortOrder());
        self::assertSame(1, $directors->getGender()?->asInt());
    }

    private function createSubject() : PersonsRequestMapper
    {
        $user = $this->createStub(UserEntity::class);
        $user->method('getId')->willReturn(42);

        $userApi = $this->createStub(UserApi::class);
        $userApi->method('fetchUserByName')->willReturn($user);

        return new PersonsRequestMapper($userApi, new ListPagePreference());
    }
}
