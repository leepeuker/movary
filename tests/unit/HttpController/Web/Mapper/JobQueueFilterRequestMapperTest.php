<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Mapper;

use Movary\HttpController\Web\Mapper\JobQueueFilterRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobQueueFilterRequestMapper::class)]
class JobQueueFilterRequestMapperTest extends TestCase
{
    public function testMapsFilters() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getGetParameters')->willReturn([
            'user' => '12',
            'type' => 'tmdb_movie_sync',
            'status' => 'done',
        ]);

        $filter = (new JobQueueFilterRequestMapper())->map($request);

        self::assertSame(12, $filter->getUserId());
        self::assertFalse($filter->isWithoutUser());
        self::assertSame('tmdb_movie_sync', (string)$filter->getType());
        self::assertSame('done', (string)$filter->getStatus());
        self::assertTrue($filter->hasFilters());
    }

    public function testMapsNoUserFilter() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getGetParameters')->willReturn(['user' => 'none']);

        $filter = (new JobQueueFilterRequestMapper())->map($request);

        self::assertNull($filter->getUserId());
        self::assertTrue($filter->isWithoutUser());
    }

    public function testIgnoresInvalidFilters() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getGetParameters')->willReturn([
            'user' => '-1',
            'type' => 'invalid',
            'status' => [],
        ]);

        $filter = (new JobQueueFilterRequestMapper())->map($request);

        self::assertNull($filter->getUserId());
        self::assertNull($filter->getType());
        self::assertNull($filter->getStatus());
        self::assertFalse($filter->hasFilters());
    }
}
