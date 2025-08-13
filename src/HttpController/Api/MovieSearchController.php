<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Api\Tmdb\TmdbApi;
use Movary\HttpController\Api\RequestMapper\SearchRequestMapper;
use Movary\HttpController\Api\ResponseMapper\MovieSearchResponseMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class MovieSearchController
{
    public function __construct(
        private readonly TmdbApi $tmdbApi,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
        private readonly SearchRequestMapper $searchRequestMapper,
        private readonly MovieSearchResponseMapper $movieSearchResponseMapper,
    ) {
    }

    public function search(Request $request) : Response
    {
        $requestData = $this->searchRequestMapper->mapRequest($request);

        $searchterm = $requestData->getSearchTerm();

        // TODO ckeck if $searchterm is a TMDB url
        if (str_starts_with($searchterm, 'https://') === true) {
            // TODO extract TMDB id from $searchterm url
            $tmdbId = 42;

            $movieDetails = $this->tmdbApi->fetchMovieDetails($tmdbId);

            // TODO implement mapMovieSearchResult(), match the mapMovieSearchResults() return type!
            $results = $this->movieSearchResponseMapper->mapMovieSearchResult($movieDetails);
        } else {
            $tmdbResponse = $this->tmdbApi->searchMovie(
                $requestData->getSearchTerm(),
                $requestData->getYear(),
                $requestData->getPage(),
            );

            $results = $this->movieSearchResponseMapper->mapMovieSearchResults($tmdbResponse);
        }


        $paginationElements = $this->paginationElementsCalculator->createPaginationElements(
            $tmdbResponse['total_results'],
            (int)floor($tmdbResponse['total_results'] / $tmdbResponse['total_pages']),
            $requestData->getPage(),
        );

        return Response::createJson(
            Json::encode([
                'results' => $results,
                'currentPage' => $paginationElements->getCurrentPage(),
                'maxPage' => $paginationElements->getMaxPage(),
            ]),
        );
    }
}
