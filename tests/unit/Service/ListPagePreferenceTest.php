<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Movary\Service\ListPagePreference;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ListPagePreference::class)]
class ListPagePreferenceTest extends TestCase
{
    public function testReturnsValidPagePreference() : void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects(self::once())
            ->method('getCookie')
            ->with(ListPagePreference::COOKIE_NAME)
            ->willReturn(rawurlencode($this->createCookie([
                'movies' => [
                    'sortBy' => 'rating',
                    'sortOrder' => 'desc',
                    'filters' => ['ge' => 'Drama', 'la' => 'en'],
                    'perPage' => '48',
                ],
            ])));

        $preference = (new ListPagePreference())->find(
            $request,
            ListPagePreference::PAGE_MOVIES,
            ['title', 'rating'],
            ['ge', 'la'],
        );

        self::assertNotNull($preference);
        self::assertSame('rating', $preference['sortBy']);
        self::assertSame('desc', (string)$preference['sortOrder']);
        self::assertSame(['ge' => 'Drama', 'la' => 'en'], $preference['filters']);
        self::assertSame(48, $preference['perPage']);
    }

    public function testIgnoresUnsupportedAndInvalidFilters() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getCookie')->willReturn($this->createCookie([
            'movies' => [
                'sortBy' => 'title',
                'sortOrder' => 'asc',
                'filters' => ['ge' => 'Drama', 'unsupported' => 'value', 'la' => [], 'pc' => ''],
                'perPage' => '100',
            ],
        ]));

        $preference = (new ListPagePreference())->find(
            $request,
            ListPagePreference::PAGE_MOVIES,
            ['title'],
            ['ge', 'la', 'pc'],
        );

        self::assertNotNull($preference);
        self::assertSame(['ge' => 'Drama'], $preference['filters']);
        self::assertNull($preference['perPage']);
    }

    public static function provideInvalidCookie() : array
    {
        return [
            'missing cookie' => [null],
            'malformed json' => ['{'],
            'wrong version' => ['{"version":2,"pages":{}}'],
            'missing page' => ['{"version":1,"pages":{}}'],
            'unsupported sort field' => [
                '{"version":1,"pages":{"movies":{"sortBy":"addedAt","sortOrder":"desc"}}}',
            ],
            'unsupported sort order' => [
                '{"version":1,"pages":{"movies":{"sortBy":"rating","sortOrder":"newest"}}}',
            ],
            'invalid pages type' => ['{"version":1,"pages":"movies"}'],
        ];
    }

    #[DataProvider('provideInvalidCookie')]
    public function testIgnoresInvalidCookie(?string $cookie) : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getCookie')->willReturn($cookie);

        self::assertNull(
            (new ListPagePreference())->find(
                $request,
                ListPagePreference::PAGE_MOVIES,
                ['title', 'rating'],
                ['ge'],
            ),
        );
    }

    private function createCookie(array $pages) : string
    {
        return json_encode(['version' => 1, 'pages' => $pages], JSON_THROW_ON_ERROR);
    }
}
