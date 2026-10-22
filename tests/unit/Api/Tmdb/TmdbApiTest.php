<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Api\Tmdb;

use Movary\Api\Tmdb\Cache\TmdbIsoLanguageCache;
use Movary\Api\Tmdb\TmdbApi;
use Movary\Api\Tmdb\TmdbClient;
use Movary\Api\Tmdb\TmdbUrlGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TmdbApi::class)]
class TmdbApiTest extends TestCase
{
    public function testFetchEnglishMoviePostersUsesFixedLanguageAndMapsMinimalData() : void
    {
        $client = $this->createMock(TmdbClient::class);
        $client
            ->expects(self::once())
            ->method('get')
            ->with('/movie/42/images', ['language' => 'en-US', 'include_image_language' => 'en'])
            ->willReturn([
                'posters' => [
                    ['file_path' => '/first.jpg', 'width' => 1000],
                    ['file_path' => '/second.jpg', 'width' => 1000],
                ],
            ]);
        $subject = new TmdbApi(
            $client,
            $this->createStub(TmdbIsoLanguageCache::class),
            new TmdbUrlGenerator(),
        );

        $posters = $subject->fetchEnglishMoviePosters(42);

        self::assertSame('/first.jpg', $posters[0]->getPath());
        self::assertSame('https://image.tmdb.org/t/p/w154/first.jpg', $posters[0]->getPreviewUrl());
        self::assertSame('/second.jpg', $posters[1]->getPath());
    }

    public function testFetchEnglishMoviePostersReturnsEmptyListWithoutFallback() : void
    {
        $client = $this->createStub(TmdbClient::class);
        $client->method('get')->willReturn(['posters' => []]);
        $subject = new TmdbApi(
            $client,
            $this->createStub(TmdbIsoLanguageCache::class),
            new TmdbUrlGenerator(),
        );

        self::assertSame([], $subject->fetchEnglishMoviePosters(42));
    }
}
