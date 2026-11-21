<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Api\Tmdb\Cache;

use ArrayIterator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Movary\Api\Tmdb\Cache\TmdbImageCache;
use Movary\Api\Tmdb\TmdbUrlGenerator;
use Movary\Service\ImageCacheService;
use Movary\ValueObject\ResourceType;
use Movary\ValueObject\Url;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(TmdbImageCache::class)]
#[AllowMockObjectsWithoutExpectations]
class TmdbImageCacheTest extends TestCase
{
    public function testMovieCachingUsesPosterOverride() : void
    {
        $result = $this->createMock(Result::class);
        $result->expects(self::once())->method('iterateAssociative')->willReturn(new ArrayIterator([[
            'id' => 12,
            'poster_path' => null,
            'tmdb_poster_path' => '/default.jpg',
            'tmdb_poster_path_override' => '/override.jpg',
        ]]));

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('executeQuery')
            ->with('SELECT id, poster_path, tmdb_poster_path, tmdb_poster_path_override FROM movie', [])
            ->willReturn($result);
        $connection->expects(self::once())->method('executeStatement')
            ->with('UPDATE movie SET poster_path = ? WHERE id = ?', ['/cached.jpg', 12])
            ->willReturn(1);

        $urlGenerator = $this->createMock(TmdbUrlGenerator::class);
        $urlGenerator
            ->expects(self::once())
            ->method('generateImageUrl')
            ->with('/override.jpg')
            ->willReturn(Url::createFromString('https://example.com/override.jpg'));
        $imageCacheService = $this->createMock(ImageCacheService::class);
        $imageCacheService
            ->expects(self::once())
            ->method('cacheImage')
            ->with(
                Url::createFromString('https://example.com/override.jpg'),
                12,
                ResourceType::createMovie(),
                true,
            )
            ->willReturn('/cached.jpg');

        $subject = new TmdbImageCache($connection, $imageCacheService, $urlGenerator, new NullLogger());

        self::assertSame(1, $subject->cacheAllMovieImages());
    }

    public function testPersonCachingUsesProfileOverride() : void
    {
        $result = $this->createMock(Result::class);
        $result->expects(self::once())->method('iterateAssociative')->willReturn(new ArrayIterator([[
            'id' => 12,
            'poster_path' => null,
            'tmdb_poster_path' => '/default.jpg',
            'tmdb_poster_path_override' => '/selected.jpg',
        ]]));
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('executeQuery')
            ->with('SELECT id, poster_path, tmdb_poster_path, tmdb_poster_path_override FROM person', [])
            ->willReturn($result);
        $connection->expects(self::once())->method('executeStatement')
            ->with('UPDATE person SET poster_path = ? WHERE id = ?', ['/cached.jpg', 12])
            ->willReturn(1);
        $urlGenerator = $this->createMock(TmdbUrlGenerator::class);
        $urlGenerator->expects(self::once())->method('generateImageUrl')->with('/selected.jpg')
            ->willReturn(Url::createFromString('https://example.com/selected.jpg'));
        $imageCacheService = $this->createMock(ImageCacheService::class);
        $imageCacheService->expects(self::once())->method('cacheImage')
            ->with(Url::createFromString('https://example.com/selected.jpg'), 12, ResourceType::createPerson(), true)
            ->willReturn('/cached.jpg');

        $subject = new TmdbImageCache(
            $connection,
            $imageCacheService,
            $urlGenerator,
            new NullLogger(),
        );

        self::assertSame(1, $subject->cacheAllPersonImages());
    }
}
