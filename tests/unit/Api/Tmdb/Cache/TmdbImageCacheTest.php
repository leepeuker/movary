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
use RuntimeException;

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
                ['SELECT id, poster_path, tmdb_poster_path, tmdb_poster_path_override, tmdb_backdrop_path FROM movie', $select],
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

    public function testMovieWithoutPosterStillCachesItsBackdrop() : void
    {
        $result = $this->createStub(Result::class);
        $result->method('iterateAssociative')->willReturn(new ArrayIterator([[
            'id' => 12, 'poster_path' => null, 'tmdb_poster_path' => null, 'tmdb_backdrop_path' => '/backdrop.jpg',
        ]]));
        $select = $this->createStub(Statement::class);
        $select->method('executeQuery')->willReturn($result);
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('prepare')
            ->with('SELECT id, poster_path, tmdb_poster_path, tmdb_poster_path_override, tmdb_backdrop_path FROM movie')->willReturn($select);
        $imageCacheService = $this->createMock(ImageCacheService::class);
        $imageCacheService->expects(self::once())->method('cacheImage')
            ->with(Url::createFromString('https://image.tmdb.org/t/p/w1280/backdrop.jpg'), 12, ResourceType::createMovie(), false, true)
            ->willReturn('/cache/backdrop.jpg');
        $subject = new TmdbImageCache($connection, $imageCacheService, new TmdbUrlGenerator(), new NullLogger());

        self::assertSame(1, $subject->cacheAllMovieImages());
    }

    public function testBackdropFailureDoesNotPreventPosterCaching() : void
    {
        $result = $this->createStub(Result::class);
        $result->method('iterateAssociative')->willReturn(new ArrayIterator([[
            'id' => 12, 'poster_path' => null, 'tmdb_poster_path' => '/poster.jpg', 'tmdb_backdrop_path' => '/backdrop.jpg',
        ]]));
        $select = $this->createStub(Statement::class);
        $select->method('executeQuery')->willReturn($result);
        $update = $this->createMock(Statement::class);
        $update->expects(self::once())->method('executeStatement')->with(['/cache/poster.jpg', 12])->willReturn(1);
        $connection = $this->createStub(Connection::class);
        $connection->method('prepare')->willReturnMap([
            ['SELECT id, poster_path, tmdb_poster_path, tmdb_poster_path_override, tmdb_backdrop_path FROM movie', $select],
            ['UPDATE movie SET poster_path = ? WHERE id = ?', $update],
        ]);
        $imageCacheService = $this->createMock(ImageCacheService::class);
        $imageCacheService->expects(self::exactly(2))->method('cacheImage')
            ->willReturnCallback(static function (Url $url, int $id, ResourceType $type, bool $force, bool $backdrop = false) : string {
                self::assertSame(12, $id);
                self::assertTrue($type->isMovie());
                self::assertSame($backdrop === false, $force);
                self::assertStringContainsString($backdrop === true ? 'backdrop.jpg' : 'poster.jpg', (string)$url);
                if ($backdrop === true) {
                    throw new RuntimeException('Backdrop unavailable');
                }

                return '/cache/poster.jpg';
            });
        $subject = new TmdbImageCache($connection, $imageCacheService, new TmdbUrlGenerator(), new NullLogger());

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
        $select = $this->createMock(Statement::class);
        $select->expects(self::once())->method('executeQuery')->with([])->willReturn($result);
        $update = $this->createMock(Statement::class);
        $update->expects(self::once())->method('executeStatement')->with(['/cached.jpg', 12])->willReturn(1);
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::exactly(2))
            ->method('prepare')
            ->willReturnMap([
                ['SELECT id, poster_path, tmdb_poster_path, tmdb_poster_path_override FROM person', $select],
                ['UPDATE person SET poster_path = ? WHERE id = ?', $update],
            ]);
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
