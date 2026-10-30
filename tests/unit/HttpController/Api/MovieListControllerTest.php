<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntityList;
use Movary\HttpController\Api\MovieListController;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\Service\ImageUrlService;
use Movary\Service\PaginationElementsCalculator;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieListController::class)]
class MovieListControllerTest extends TestCase
{
    public function testReturnsLastPageOfMovieSummariesInIdOrder() : void
    {
        $request = $this->createStub(Request::class);
        $requestMapper = $this->createStub(RequestMapper::class);
        $requestMapper->method('mapLimit')->willReturn(2);
        $requestMapper->method('mapPage')->willReturn(9);
        $requestMapper->method('mapSearchTerm')->willReturn('Movie');
        $releaseYear = Year::createFromInt(2024);
        $requestMapper->method('mapReleaseYear')->willReturn($releaseYear);
        $requestMapper->method('mapSortBy')->willReturn('releaseDate');
        $sortOrder = SortOrder::createDesc();
        $requestMapper->method('mapSortOrder')->willReturn($sortOrder);
        $movieApi = $this->createMock(MovieApi::class);
        $movieApi->expects(self::once())->method('countAll')->with('Movie', $releaseYear)->willReturn(3);
        $movieApi->expects(self::once())->method('fetchAllPaginated')
            ->with(2, 2, 'Movie', 'releaseDate', $sortOrder, $releaseYear)->willReturn(
            MovieEntityList::createFromArray([$this->movieData()]),
        );
        $imageUrlService = $this->createMock(ImageUrlService::class);
        $imageUrlService->expects(self::once())->method('generateImageSrcUrlFromParameters')
            ->with('/poster.jpg', null, 'Movie')->willReturn('https://example.com/poster.jpg');

        $response = (new MovieListController($movieApi, $requestMapper, new PaginationElementsCalculator(), $imageUrlService))
            ->getMovies($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        self::assertContainsEquals(Header::createCacheControlNoStore(), $response->getHeaders());
        self::assertSame([
            'movies' => [[
                'id' => 12,
                'tmdbId' => 34,
                'title' => 'Movie',
                'releaseDate' => '2024-03-15',
                'posterUrl' => 'https://example.com/poster.jpg',
            ]],
            'currentPage' => 2,
            'maxPage' => 2,
            'totalCount' => 3,
        ], json_decode((string)$response->getBody(), true));
    }

    public function testReturnsEmptyCatalog() : void
    {
        $requestMapper = $this->createStub(RequestMapper::class);
        $requestMapper->method('mapLimit')->willReturn(24);
        $requestMapper->method('mapPage')->willReturn(1);
        $requestMapper->method('mapSortBy')->willReturn('id');
        $sortOrder = SortOrder::createAsc();
        $requestMapper->method('mapSortOrder')->willReturn($sortOrder);
        $movieApi = $this->createMock(MovieApi::class);
        $movieApi->expects(self::once())->method('countAll')->with(null, null)->willReturn(0);
        $movieApi->expects(self::once())->method('fetchAllPaginated')
            ->with(24, 0, null, 'id', $sortOrder, null)->willReturn(MovieEntityList::create());
        $imageUrlService = $this->createMock(ImageUrlService::class);
        $imageUrlService->expects(self::never())->method('generateImageSrcUrlFromParameters');

        $response = (new MovieListController($movieApi, $requestMapper, new PaginationElementsCalculator(), $imageUrlService))
            ->getMovies($this->createStub(Request::class));

        self::assertSame('{"movies":[],"currentPage":1,"maxPage":1,"totalCount":0}', $response->getBody());
    }

    private function movieData() : array
    {
        return [
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
            'release_date' => '2024-03-15',
            'tmdb_vote_average' => null,
            'tmdb_vote_count' => null,
            'tmdb_poster_path' => '/poster.jpg',
            'tmdb_poster_path_override' => null,
            'imdb_rating_average' => null,
            'imdb_rating_vote_count' => null,
            'updated_at_tmdb' => null,
            'updated_at_imdb' => null,
        ];
    }
}
