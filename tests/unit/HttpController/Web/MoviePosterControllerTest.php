<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Api\Tmdb\Dto\TmdbPoster;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\Movie\MoviePosterService;
use Movary\HttpController\Web\Movie\MoviePosterController;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MoviePosterController::class)]
#[AllowMockObjectsWithoutExpectations]
class MoviePosterControllerTest extends TestCase
{
    private MockObject|MovieApi $movieApi;
    private MockObject|MoviePosterService $posterService;
    private MoviePosterController $subject;

    protected function setUp() : void
    {
        $this->movieApi = $this->createMock(MovieApi::class);
        $this->posterService = $this->createMock(MoviePosterService::class);
        $this->subject = new MoviePosterController($this->movieApi, $this->posterService);
    }

    public function testFetchPostersReturnsNotFoundForUnknownMovie() : void
    {
        $this->movieApi->method('findById')->with(12)->willReturn(null);

        self::assertSame(404, $this->subject->fetchPosters($this->createRequest())->getStatusCode()->getCode());
    }

    public function testFetchPostersReturnsMinimalJsonResponse() : void
    {
        $movie = $this->createMovie('/override.jpg');
        $this->movieApi->method('findById')->willReturn($movie);
        $this->posterService->method('fetchAvailablePosters')->with($movie)->willReturn([
            new TmdbPoster('/poster.jpg', 'https://example.com/poster.jpg'),
        ]);

        $response = $this->subject->fetchPosters($this->createRequest());

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertSame(
            '{"posters":[{"path":"\/poster.jpg","previewUrl":"https:\/\/example.com\/poster.jpg"}],"hasOverride":true}',
            $response->getBody(),
        );
    }

    public function testSelectPosterReturnsUnprocessableForInvalidJson() : void
    {
        $this->movieApi->method('findById')->willReturn($this->createMovie(null));

        $response = $this->subject->selectPoster($this->createRequest('{'));

        self::assertSame(422, $response->getStatusCode()->getCode());
    }

    public function testSelectPosterDelegatesValidSelection() : void
    {
        $movie = $this->createMovie(null);
        $this->movieApi->method('findById')->willReturn($movie);
        $this->posterService->expects(self::once())->method('select')->with($movie, '/poster.jpg');

        $response = $this->subject->selectPoster($this->createRequest('{"posterPath":"/poster.jpg"}'));

        self::assertSame(204, $response->getStatusCode()->getCode());
    }

    public function testRestoreDefaultDelegatesReset() : void
    {
        $movie = $this->createMovie('/override.jpg');
        $this->movieApi->method('findById')->willReturn($movie);
        $this->posterService->expects(self::once())->method('restoreDefault')->with($movie);

        $response = $this->subject->restoreDefault($this->createRequest());

        self::assertSame(204, $response->getStatusCode()->getCode());
    }

    private function createRequest(string $body = '') : Request&MockObject
    {
        $request = $this->createMock(Request::class);
        $request->method('getRouteParameters')->willReturn(['id' => '12']);
        $request->method('getBody')->willReturn($body);

        return $request;
    }

    private function createMovie(?string $override) : MovieEntity
    {
        return MovieEntity::createFromArray([
            'id' => 12,
            'title' => 'Movie',
            'original_title' => null,
            'trakt_id' => null,
            'imdb_id' => null,
            'tmdb_id' => 34,
            'poster_path' => null,
            'overview' => null,
            'tagline' => null,
            'original_language' => 'en',
            'runtime' => 90,
            'release_date' => null,
            'tmdb_vote_average' => null,
            'tmdb_vote_count' => null,
            'tmdb_poster_path' => '/default.jpg',
            'tmdb_poster_path_override' => $override,
            'imdb_rating_average' => null,
            'imdb_rating_vote_count' => null,
            'updated_at_tmdb' => null,
            'updated_at_imdb' => null,
        ]);
    }
}
