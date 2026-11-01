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
    public function testFetchPersonDetailsAppendsExternalIds() : void
    {
        $client = $this->createMock(TmdbClient::class);
        $client->expects(self::once())->method('get')
            ->with('/person/42', ['append_to_response' => 'external_ids'])
            ->willReturn([
                'id' => 42,
                'imdb_id' => null,
                'name' => 'Example',
                'biography' => '',
                'birthday' => null,
                'deathday' => null,
                'gender' => 0,
                'popularity' => 1.0,
                'known_for_department' => null,
                'profile_path' => null,
                'place_of_birth' => null,
                'external_ids' => ['instagram_id' => 'example'],
            ]);
        $subject = new TmdbApi($client, $this->createStub(TmdbIsoLanguageCache::class), new TmdbUrlGenerator());

        self::assertSame('example', $subject->fetchPersonDetails(42)->getExternalIds()['instagram_id']);
    }

    public function testFetchPersonProfilesMapsAvailableImages() : void
    {
        $client = $this->createMock(TmdbClient::class);
        $client->expects(self::once())->method('get')->with('/person/42/images')->willReturn([
            'profiles' => [
                ['file_path' => '/first.jpg'],
                ['file_path' => ''],
                ['file_path' => '/second.jpg'],
            ],
        ]);
        $subject = new TmdbApi($client, $this->createStub(TmdbIsoLanguageCache::class), new TmdbUrlGenerator());

        $profiles = $subject->fetchPersonProfiles(42);

        self::assertCount(2, $profiles);
        self::assertSame(['path' => '/first.jpg', 'previewUrl' => 'https://image.tmdb.org/t/p/w154/first.jpg'], $profiles[0]->asArray());
        self::assertSame('/second.jpg', $profiles[1]->getPath());
    }

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
