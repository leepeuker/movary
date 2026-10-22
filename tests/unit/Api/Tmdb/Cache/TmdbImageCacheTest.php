<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Api\Tmdb\Cache;

use ArrayIterator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
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

        $select = $this->createMock(Statement::class);
        $select->expects(self::once())->method('executeQuery')->with([])->willReturn($result);
        $update = $this->createMock(Statement::class);
        $update->expects(self::once())->method('executeStatement')->with(['/cached.jpg', 12])->willReturn(1);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::exactly(2))
            ->method('prepare')
            ->willReturnMap([
                ['SELECT id, poster_path, tmdb_poster_path, tmdb_poster_path_override FROM movie', $select],
                ['UPDATE movie SET poster_path = ? WHERE id = ?', $update],
            ]);

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

    public function testPersonCachingRetainsDefaultPosterQuery() : void
    {
        $result = $this->createMock(Result::class);
        $result->expects(self::once())->method('iterateAssociative')->willReturn(new ArrayIterator());
        $statement = $this->createMock(Statement::class);
        $statement->expects(self::once())->method('executeQuery')->with([])->willReturn($result);
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('prepare')
            ->with('SELECT id, poster_path, tmdb_poster_path FROM person')
            ->willReturn($statement);

        $subject = new TmdbImageCache(
            $connection,
            $this->createStub(ImageCacheService::class),
            $this->createStub(TmdbUrlGenerator::class),
            new NullLogger(),
        );

        self::assertSame(0, $subject->cacheAllPersonImages());
    }
}
