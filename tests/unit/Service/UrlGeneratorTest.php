<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Movary\Api\Tmdb\TmdbUrlGenerator;
use Movary\Service\ImageCacheService;
use Movary\Service\UrlGenerator;
use Movary\ValueObject\Url;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/** @covers \Movary\Service\UrlGenerator */
class UrlGeneratorTest extends TestCase
{
    private ImageCacheService|MockObject $imageCacheServiceMock;

    private UrlGenerator $subject;

    private MockObject|TmdbUrlGenerator $tmdbUrlGeneratorMock;

    public function provideTestGenerateImageSrcUrlFromParametersData() : array
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

        $this->subject = new UrlGenerator(
            $this->tmdbUrlGeneratorMock,
            $this->imageCacheServiceMock,
            true
        );
    }

    /** @dataProvider provideTestGenerateImageSrcUrlFromParametersData */
    public function testGenerateImageSrcUrlFromParameters(?string $tmdbPosterPath, ?string $posterPath, string $expectedResult, bool $posterPathExists) : void
    {
        if ($posterPath !== null) {
            $this->imageCacheServiceMock
                ->expects(self::once())
                ->method('posterPathExists')
                ->with($posterPath)
                ->willReturn($posterPathExists);
        }

        if ($posterPath === null && $tmdbPosterPath !== null) {
            $this->tmdbUrlGeneratorMock
                ->expects(self::once())
                ->method('generateImageUrl')
                ->with($tmdbPosterPath)
                ->willReturn(Url::createFromString($expectedResult));
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

        self::assertEquals(
            $expectedResult,
            $this->subject->replacePosterPathWithImageSrcUrl($dbResults),
        );
    }
}
