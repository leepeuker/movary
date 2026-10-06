<?php declare(strict_types=1);

namespace Tests\Unit\Movary\JobQueue;

use Movary\JobQueue\JobQueueFilter;
use Movary\ValueObject\JobStatus;
use Movary\ValueObject\JobType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobQueueFilter::class)]
class JobQueueFilterTest extends TestCase
{
    public function testCreatesEmptyFilter() : void
    {
        $filter = JobQueueFilter::create();

        self::assertFalse($filter->hasFilters());
        self::assertNull($filter->getUserId());
        self::assertFalse($filter->isWithoutUser());
        self::assertNull($filter->getType());
        self::assertNull($filter->getStatus());
    }

    public function testCreatesPopulatedFilter() : void
    {
        $filter = JobQueueFilter::create(
            12,
            false,
            JobType::createTmdbMovieSync(),
            JobStatus::createDone(),
        );

        self::assertTrue($filter->hasFilters());
        self::assertSame(12, $filter->getUserId());
        self::assertSame('tmdb_movie_sync', (string)$filter->getType());
        self::assertSame('done', (string)$filter->getStatus());
    }
}
