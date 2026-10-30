<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\HttpController\Api\MovieDetailController;
use Movary\Service\ImageUrlService;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieDetailController::class)]
class MovieDetailControllerTest extends TestCase
{
    public function testReturnsSharedMovieMetadata() : void
    {
        $movie = $this->createStub(MovieEntity::class);
        $movie->method('getId')->willReturn(12);
        $movie->method('getTmdbId')->willReturn(34);
        $movie->method('getTraktId')->willReturn(TraktId::createFromInt(56));
        $movie->method('getImdbId')->willReturn('tt1234567');
        $movie->method('getTitle')->willReturn('Movie');
        $movie->method('getOriginalTitle')->willReturn('Original Movie');
        $movie->method('getReleaseDate')->willReturn(DateTime::createFromString('2024-03-15'));
        $movie->method('getTagline')->willReturn('A tagline');
        $movie->method('getOverview')->willReturn('An overview');
        $movie->method('getOriginalLanguage')->willReturn('en');
        $movie->method('getRuntime')->willReturn(124);
        $movie->method('getTmdbPosterPathEffective')->willReturn('/override.jpg');
        $movie->method('getPosterPath')->willReturn('posters/cached.jpg');
        $movie->method('getTmdbVoteAverage')->willReturn(7.2);
        $movie->method('getTmdbVoteCount')->willReturn(100);
        $movie->method('getImdbRatingAverage')->willReturn(8.1);
        $movie->method('getImdbVoteCount')->willReturn(200);
        $movieApi = $this->createMock(MovieApi::class);
        $movieApi->expects(self::once())->method('findById')->with(12)->willReturn($movie);
        $imageUrlService = $this->createMock(ImageUrlService::class);
        $imageUrlService->expects(self::once())->method('generateImageSrcUrlFromParameters')
            ->with('/override.jpg', 'posters/cached.jpg', 'Movie')->willReturn('https://example.com/poster.jpg');

        $response = (new MovieDetailController($movieApi, $imageUrlService))->getMovie($this->request());

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame([
            'id' => 12,
            'tmdbId' => 34,
            'traktId' => 56,
            'imdbId' => 'tt1234567',
            'title' => 'Movie',
            'originalTitle' => 'Original Movie',
            'releaseDate' => '2024-03-15',
            'tagline' => 'A tagline',
            'overview' => 'An overview',
            'originalLanguage' => 'en',
            'runtime' => 124,
            'posterUrl' => 'https://example.com/poster.jpg',
            'tmdbRatingAverage' => 7.2,
            'tmdbRatingVoteCount' => 100,
            'imdbRatingAverage' => 8.1,
            'imdbRatingVoteCount' => 200,
        ], json_decode((string)$response->getBody(), true));
    }

    public function testReturnsNotFoundWithoutGeneratingPosterForUnknownMovie() : void
    {
        $movieApi = $this->createMock(MovieApi::class);
        $movieApi->expects(self::once())->method('findById')->with(12)->willReturn(null);
        $imageUrlService = $this->createMock(ImageUrlService::class);
        $imageUrlService->expects(self::never())->method('generateImageSrcUrlFromParameters');

        $response = (new MovieDetailController($movieApi, $imageUrlService))->getMovie($this->request());

        self::assertSame(404, $response->getStatusCode()->getCode());
    }

    private function request() : Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getRouteParameters')->willReturn(['movaryId' => '12']);

        return $request;
    }
}
