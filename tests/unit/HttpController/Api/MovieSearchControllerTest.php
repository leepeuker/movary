<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api;

use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Api\Dto\MovieSearchResultDtoList;
use Movary\HttpController\Api\Dto\SearchRequestDto;
use Movary\HttpController\Api\MovieSearchController;
use Movary\HttpController\Api\RequestMapper\SearchRequestMapper;
use Movary\HttpController\Api\ResponseMapper\MovieSearchResponseMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Year;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieSearchController::class)]
class MovieSearchControllerTest extends TestCase
{
    public function testReturnsEmptyResultWithoutMappingMovies() : void
    {
        $request = $this->createStub(Request::class);
        $tmdb = $this->createMock(TmdbApi::class);
        $tmdb->expects(self::once())->method('searchMovie')->with('Alien', null, 1)->willReturn([
            'results' => [],
            'total_results' => 0,
        ]);
        $responseMapper = $this->createMock(MovieSearchResponseMapper::class);
        $responseMapper->expects(self::never())->method('mapMovieSearchResults');

        $response = $this->createController($tmdb, $responseMapper, SearchRequestDto::create('Alien', 1, null))
            ->search($request);

        self::assertSame('{"results":[],"currentPage":0,"maxPage":0}', $response->getBody());
    }

    public function testMapsSearchAndPagination() : void
    {
        $request = $this->createStub(Request::class);
        $year = Year::createFromInt(1979);
        $tmdbResponse = ['results' => [['id' => 348]], 'total_results' => 40, 'total_pages' => 2];
        $tmdb = $this->createMock(TmdbApi::class);
        $tmdb->expects(self::once())->method('searchMovie')->with('Alien', $year, 2)->willReturn($tmdbResponse);
        $responseMapper = $this->createMock(MovieSearchResponseMapper::class);
        $responseMapper
            ->expects(self::once())
            ->method('mapMovieSearchResults')
            ->with(12, $tmdbResponse)
            ->willReturn(MovieSearchResultDtoList::create());

        $response = $this->createController($tmdb, $responseMapper, SearchRequestDto::create('Alien', 2, $year))
            ->search($request);

        self::assertSame('{"results":[],"currentPage":2,"maxPage":2}', $response->getBody());
    }

    private function createController(
        TmdbApi $tmdb,
        MovieSearchResponseMapper $responseMapper,
        SearchRequestDto $requestData,
    ) : MovieSearchController {
        $requestMapper = $this->createStub(SearchRequestMapper::class);
        $requestMapper->method('mapRequest')->willReturn($requestData);
        $authentication = $this->createStub(Authentication::class);
        $authentication->method('requireApiToken')->willReturn(
            AuthenticatedUser::create(12, CredentialType::API_TOKEN),
        );

        return new MovieSearchController(
            $tmdb,
            new PaginationElementsCalculator(),
            $requestMapper,
            $responseMapper,
            $authentication,
        );
    }
}
