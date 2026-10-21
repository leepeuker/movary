<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\RequestMapper;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testMapsJsonObject() : void
    {
        self::assertSame(
            ['tmdbId' => 42],
            $this->subject->mapJsonObject($this->createRequest('{"tmdbId":42}')),
        );
    }

    public function testRejectsJsonListWhenObjectIsRequired() : void
    {
        $this->expectExceptionObject(InvalidApiRequest::invalidJsonObject());

        $this->subject->mapJsonObject($this->createRequest('[{"tmdbId":42}]'));
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

    public function testMapsOptionalObjectList() : void
    {
        self::assertSame(
            [['watchedAt' => null]],
            $this->subject->mapOptionalObjectList(
                ['watchDates' => [['watchedAt' => null]]],
                'watchDates',
                'items[0].watchDates',
            ),
        );
    }

    public function testRejectsNonObjectListItem() : void
    {
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('items[0].watchDates[0]', 'must be an object'),
        );

        $this->subject->mapOptionalObjectList(
            ['watchDates' => ['2026-10-20']],
            'watchDates',
            'items[0].watchDates',
        );
    }

    public function testMapsNullableDate() : void
    {
        self::assertNull($this->subject->mapNullableDate(null, 'items[0].watchedAt'));
        self::assertSame(
            '2026-10-20',
            (string)$this->subject->mapNullableDate('2026-10-20', 'items[0].watchedAt'),
        );
    }

    public function testMapsQueryDefaultsAndBoundaries() : void
    {
        self::assertSame(1, $this->subject->mapPage($this->createQueryRequest([])));
        self::assertSame(24, $this->subject->mapLimit($this->createQueryRequest([])));
        self::assertSame(1, $this->subject->mapPage($this->createQueryRequest(['page' => '1'])));
        self::assertSame(100, $this->subject->mapLimit($this->createQueryRequest(['limit' => '100'])));
    }

    /** @param mixed $value */
    #[DataProvider('invalidPositiveQueryIntegerProvider')]
    public function testRejectsInvalidPositiveQueryInteger(string $field, mixed $value, string $requirement) : void
    {
        $this->expectExceptionObject(InvalidApiRequest::invalidField($field, $requirement));

        if ($field === 'page') {
            $this->subject->mapPage($this->createQueryRequest([$field => $value]));
        } else {
            $this->subject->mapLimit($this->createQueryRequest([$field => $value]));
        }
    }

    public static function invalidPositiveQueryIntegerProvider() : array
    {
        return [
            'non-numeric page' => ['page', 'first', 'must be a positive integer'],
            'zero page' => ['page', '0', 'must be a positive integer'],
            'negative page' => ['page', '-1', 'must be a positive integer'],
            'array page' => ['page', ['1'], 'must be a positive integer'],
            'integer overflow' => ['page', '999999999999999999999999', 'must be a positive integer'],
            'limit above maximum' => ['limit', '101', 'must be at most 100'],
        ];
    }

    public function testMapsAndValidatesSearchTerm() : void
    {
        self::assertNull($this->subject->mapSearchTerm($this->createQueryRequest([])));
        self::assertSame('Alien', $this->subject->mapSearchTerm($this->createQueryRequest(['search' => 'Alien'])));

        $this->expectExceptionObject(InvalidApiRequest::invalidField('search', 'must be a string'));
        $this->subject->mapSearchTerm($this->createQueryRequest(['search' => ['Alien']]));
    }

    public function testRejectsMissingRequiredSearchTerm() : void
    {
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('search', 'is required and must be a string'),
        );

        $this->subject->mapRequiredSearchTerm($this->createQueryRequest([]));
    }

    public function testMapsAndValidatesSortValues() : void
    {
        $request = $this->createQueryRequest([]);
        self::assertSame('title', $this->subject->mapSortBy($request, ['title', 'rating'], 'title'));
        self::assertSame('desc', (string)$this->subject->mapSortOrder($request));
        self::assertSame(
            'rating',
            $this->subject->mapSortBy(
                $this->createQueryRequest(['sortBy' => 'rating']),
                ['title', 'rating'],
                'title',
            ),
        );

        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('sortBy', 'must be one of: title, rating'),
        );
        $this->subject->mapSortBy(
            $this->createQueryRequest(['sortBy' => 'unsupported']),
            ['title', 'rating'],
            'title',
        );
    }

    public function testRejectsInvalidSortOrder() : void
    {
        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('sortOrder', 'must be one of: asc, desc'),
        );

        $this->subject->mapSortOrder($this->createQueryRequest(['sortOrder' => 'unsupported']));
    }

    public function testMapsAndValidatesReleaseYear() : void
    {
        self::assertNull($this->subject->mapReleaseYear($this->createQueryRequest([])));
        self::assertSame(
            1901,
            $this->subject->mapReleaseYear($this->createQueryRequest(['releaseYear' => '1901']))?->asInt(),
        );
        self::assertSame(
            2155,
            $this->subject->mapReleaseYear($this->createQueryRequest(['releaseYear' => '2155']))?->asInt(),
        );

        $this->expectExceptionObject(
            InvalidApiRequest::invalidField('releaseYear', 'must be a year between 1901 and 2155'),
        );
        $this->subject->mapReleaseYear($this->createQueryRequest(['releaseYear' => '2156']));
    }

    private function createRequest(string $body) : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn($body);

        return $request;
    }

    /** @param array<string, mixed> $query */
    private function createQueryRequest(array $query) : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getGetParameters')->willReturn($query);

        return $request;
    }
}
