<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Mapper;

use Movary\HttpController\Web\Mapper\JobScheduleFilterRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobScheduleFilterRequestMapper::class)]
class JobScheduleFilterRequestMapperTest extends TestCase
{
    public function testMapsFilters() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getGetParameters')->willReturn([
            'user' => '12',
            'type' => 'tmdb_movie_sync',
            'status' => 'disabled',
        ]);

        $filter = (new JobScheduleFilterRequestMapper())->map($request);

        self::assertSame(12, $filter->getUserId());
        self::assertSame('tmdb_movie_sync', (string)$filter->getType());
        self::assertFalse($filter->getEnabled());
        self::assertTrue($filter->hasFilters());
    }

    public function testMapsNoUserFilter() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getGetParameters')->willReturn(['user' => 'none']);

        $filter = (new JobScheduleFilterRequestMapper())->map($request);

        self::assertNull($filter->getUserId());
        self::assertTrue($filter->isWithoutUser());
    }

    public function testIgnoresInvalidFilters() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getGetParameters')->willReturn([
            'user' => '-1',
            'type' => 'trakt_import_history',
            'status' => 'waiting',
        ]);

        $filter = (new JobScheduleFilterRequestMapper())->map($request);

        self::assertNull($filter->getUserId());
        self::assertNull($filter->getType());
        self::assertNull($filter->getEnabled());
        self::assertFalse($filter->hasFilters());
    }
}
