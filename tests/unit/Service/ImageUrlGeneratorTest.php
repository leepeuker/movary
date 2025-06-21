<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Movary\Api\Tmdb\TmdbUrlGenerator;
use Movary\Service\ApplicationUrlService;
use Movary\Service\ImageCacheService;
use Movary\Service\ImageUrlService;
use Movary\ValueObject\RelativeUrl;
use Movary\ValueObject\Url;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Service\ImageUrlService::class)]
class ImageUrlGeneratorTest extends TestCase
{
    private ImageCacheService|MockObject $imageCacheServiceMock;

    private ApplicationUrlService|MockObject $applicationUrlServiceMock;

    private ImageUrlService $subject;

    private MockObject|TmdbUrlGenerator $tmdbUrlGeneratorMock;

    public static function provideTestGenerateImageSrcUrlFromParametersData() : array
    {
        return [
            [
                'tmdbPosterPath' => 'foo',
                'posterPath' => 'bar',
                'expectedResult' => '/bar',
                'posterPathExists' => true,
            ],
            [
                'tmdbPosterPath' => 'foo',
                'posterPath' => 'bar',
                'expectedResult' => '/bar',
                'posterPathExists' => true,
            ],
            [
                'tmdbPosterPath' => null,
                'posterPath' => 'bar',
                'expectedResult' => '/bar',
                'posterPathExists' => true,
            ],
            [
                'tmdbPosterPath' => null,
                'posterPath' => 'bar',
                'expectedResult' => '/images/placeholder-image.png',
                'posterPathExists' => false,
            ],
            [
                'tmdbPosterPath' => 'foo',
                'posterPath' => null,
                'expectedResult' => 'http://localhost',
                'posterPathExists' => true,
            ],
            [
                'tmdbPosterPath' => null,
                'posterPath' => null,
                'expectedResult' => '/images/placeholder-image.png',
                'posterPathExists' => true,
            ],
        ];
    }

    public function setUp() : void
    {
        $this->tmdbUrlGeneratorMock = $this->createMock(TmdbUrlGenerator::class);
        $this->imageCacheServiceMock = $this->createMock(ImageCacheService::class);
        $this->applicationUrlServiceMock = $this->createMock(ApplicationUrlService::class);

        $this->subject = new ImageUrlService(
            $this->tmdbUrlGeneratorMock,
            $this->imageCacheServiceMock,
            $this->applicationUrlServiceMock,
            true,
        );
    }

    #[DataProvider('provideTestGenerateImageSrcUrlFromParametersData')]
    public function testGenerateImageSrcUrlFromParameters(?string $tmdbPosterPath, ?string $posterPath, string $expectedResult, bool $posterPathExists) : void
    {
        if ($posterPath !== null) {
            $this->imageCacheServiceMock
                ->expects(self::once())
                ->method('posterPathExists')
                ->with($posterPath)
                ->willReturn($posterPathExists);

            $this->applicationUrlServiceMock
                ->expects(self::once())
                ->method('createApplicationUrl')
                ->with(RelativeUrl::create($expectedResult))
                ->willReturn($expectedResult);
        }

        if ($posterPath === null && $tmdbPosterPath !== null) {
            $this->tmdbUrlGeneratorMock
                ->expects(self::once())
                ->method('generateImageUrl')
                ->with($tmdbPosterPath)
                ->willReturn(Url::createFromString($expectedResult));
        }

        if ($posterPath === null && $tmdbPosterPath === null) {
            $this->applicationUrlServiceMock
                ->expects(self::once())
                ->method('createApplicationUrl')
                ->with(RelativeUrl::create($expectedResult))
                ->willReturn($expectedResult);
        }

        self::assertEquals(
            $expectedResult,
            $this->subject->generateImageSrcUrlFromParameters($tmdbPosterPath, $posterPath),
        );
    }

    public function testReplacePosterPathWithImageSrcUrl() : void
    {
        $dbResults = [];
        $dbResults[] = [
            'poster_path' => 'poster_path',
            'tmdb_poster_path' => 'tmdb_poster_path',
        ];
        $dbResults[] = [
            'poster_path' => null,
            'tmdb_poster_path' => 'tmdb_poster_path',
        ];

        $this->imageCacheServiceMock
            ->expects(self::once())
            ->method('posterPathExists')
            ->with('poster_path')
            ->willReturn(true);

        $this->tmdbUrlGeneratorMock
            ->expects(self::once())
            ->method('generateImageUrl')
            ->with('tmdb_poster_path')
            ->willReturn(Url::createFromString('http://localhost'));

        $expectedResult = [];
        $expectedResult[] = [
            'poster_path' => '/poster_path',
            'tmdb_poster_path' => 'tmdb_poster_path'
        ];
        $expectedResult[] = [
            'poster_path' => 'http://localhost',
            'tmdb_poster_path' => 'tmdb_poster_path'
        ];

        $this->applicationUrlServiceMock
            ->expects(self::once())
            ->method('createApplicationUrl')
            ->with(RelativeUrl::create('/poster_path'))
            ->willReturn('/poster_path');

        self::assertEquals(
            $expectedResult,
            $this->subject->replacePosterPathWithImageSrcUrl($dbResults),
        );
    }
}
