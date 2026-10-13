<?php declare(strict_types=1);

namespace Tests\Unit\Movary\JobQueue;

use Movary\JobQueue\JobScheduleFilter;
use Movary\ValueObject\JobType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobScheduleFilter::class)]
class JobScheduleFilterTest extends TestCase
{
    public function testCreatesEmptyFilter() : void
    {
        $filter = JobScheduleFilter::create();

        self::assertFalse($filter->hasFilters());
        self::assertNull($filter->getUserId());
        self::assertFalse($filter->isWithoutUser());
        self::assertNull($filter->getType());
        self::assertNull($filter->getEnabled());
    }

    public function testCreatesPopulatedFilter() : void
    {
        $filter = JobScheduleFilter::create(12, false, JobType::createTmdbMovieSync(), false);

        self::assertTrue($filter->hasFilters());
        self::assertSame(12, $filter->getUserId());
        self::assertSame('tmdb_movie_sync', (string)$filter->getType());
        self::assertFalse($filter->getEnabled());
    }
}
