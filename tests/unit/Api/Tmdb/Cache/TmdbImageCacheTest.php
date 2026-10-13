<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Api\Tmdb\Cache;

use Movary\Api\Tmdb\Cache\TmdbImageCache;
use Movary\JobQueue\JobEntity;
use Movary\Util\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TmdbImageCache::class)]
class TmdbImageCacheTest extends TestCase
{
    public function testExecuteRefreshJobCachesAllImages() : void
    {
        $cache = $this->getMockBuilder(TmdbImageCache::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['cacheAllMovieImages', 'cacheAllPersonImages'])
            ->getMock();
        $cache->expects(self::once())->method('cacheAllMovieImages')->with(true);
        $cache->expects(self::once())->method('cacheAllPersonImages')->with(true);

        $cache->executeJob(JobEntity::createFromArray([
            'id' => 5,
            'job_type' => 'tmdb_image_cache',
            'job_status' => 'waiting',
            'user_id' => 42,
            'parameters' => Json::encode([
                'refreshAll' => true,
                'imageType' => 'all',
                'forceRefresh' => true,
            ]),
            'updated_at' => null,
            'created_at' => '2026-10-13 12:00:00',
        ]));
    }
}
