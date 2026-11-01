<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use GuzzleHttp\Psr7\Response;
use Movary\Service\ImageCacheService;
use Movary\Util\File;
use Movary\ValueObject\ResourceType;
use Movary\ValueObject\Url;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\NullLogger;

#[CoversClass(ImageCacheService::class)]
class ImageCacheServiceTest extends TestCase
{
    private string $directory;

    protected function setUp() : void
    {
        $this->directory = sys_get_temp_dir() . '/movary-backdrop-' . bin2hex(random_bytes(8)) . '/';
    }

    protected function tearDown() : void
    {
        (new File())->deleteDirectoryContentRecursively($this->directory);
    }

    public function testBackdropsNeverOverwritePostersAndChangedPathsGetNewFiles() : void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::exactly(3))->method('sendRequest')->willReturnCallback(static fn() : Response => new Response(200, [], 'image bytes'));
        $subject = new ImageCacheService(new File(), new NullLogger(), $client, $this->createStub(Connection::class), $this->directory, '/cache');
        $backdropUrl = Url::createFromString('https://image.tmdb.org/t/p/w1280/backdrop.jpg');
        $newBackdropUrl = Url::createFromString('https://image.tmdb.org/t/p/w1280/new-backdrop.jpg');

        $poster = $subject->cacheImage(Url::createFromString('https://image.tmdb.org/t/p/w342/poster.jpg'), 12, ResourceType::createMovie(), false);
        $backdrop = $subject->cacheImage($backdropUrl, 12, ResourceType::createMovie(), false, true);
        $newBackdrop = $subject->cacheImage($newBackdropUrl, 12, ResourceType::createMovie(), false, true);

        self::assertSame('/cache/movie/12.jpg', $poster);
        self::assertSame($subject->generateMovieBackdropPath(12, $backdropUrl), $backdrop);
        self::assertNotSame($poster, $backdrop);
        self::assertNotSame($backdrop, $newBackdrop);
        self::assertNotNull($backdrop);
        self::assertTrue($subject->posterPathExists($backdrop));
        self::assertSame('image bytes', file_get_contents($this->directory . ltrim($backdrop, '/')));
        self::assertNull($subject->cacheImage($backdropUrl, 12, ResourceType::createMovie(), false, true));
    }

    public function testCleanupRemovesStaleBackdropsAndDeletedMovies() : void
    {
        $currentResult = $this->createStub(Result::class);
        $currentResult->method('fetchAssociative')->willReturn(['id' => 12, 'tmdb_backdrop_path' => '/current.jpg']);
        $deletedResult = $this->createStub(Result::class);
        $deletedResult->method('fetchAssociative')->willReturn(false);
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::exactly(4))->method('executeQuery')
            ->with('SELECT id, tmdb_backdrop_path FROM movie WHERE id = ?', self::anything())
            ->willReturnCallback(static function (string $query, array $parameters) use ($currentResult, $deletedResult) : Result {
                self::assertSame('SELECT id, tmdb_backdrop_path FROM movie WHERE id = ?', $query);

                return $parameters === [12] ? $currentResult : $deletedResult;
            });
        $subject = new ImageCacheService(new File(), new NullLogger(), $this->createStub(ClientInterface::class), $connection, $this->directory, '/cache');
        $current = $subject->generateMovieBackdropPath(12, Url::createFromString('https://image.tmdb.org/t/p/w1280/current.jpg'));
        $old = $subject->generateMovieBackdropPath(12, Url::createFromString('https://image.tmdb.org/t/p/w1280/old.jpg'));
        (new File())->createDirectory($this->directory . 'cache/movie', 0755);
        foreach (['/cache/movie/12.jpg', '/cache/movie/99.jpg', $current, $old] as $path) {
            (new File())->createFile($this->directory . ltrim($path, '/'), 'image');
        }

        self::assertSame(2, $subject->deleteOutdatedImagesByResourceType(ResourceType::createMovie()));
        self::assertTrue($subject->posterPathExists('/cache/movie/12.jpg'));
        self::assertTrue($subject->posterPathExists($current));
        self::assertFalse($subject->posterPathExists($old));
        self::assertFalse($subject->posterPathExists('/cache/movie/99.jpg'));
    }
}
