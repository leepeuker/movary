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
        private readonly MovieSearchResponseMapper $historyResponseMapper,
    ) {
    }

    public function search(Request $request) : Response
    {
        $requestData = $this->searchRequestMapper->mapRequest($request);

        $tmdbResponse = $this->tmdbApi->searchMovie(
            $requestData->getSearchTerm(),
            $requestData->getYear(),
            $requestData->getPage(),
        );

        $paginationElements = $this->paginationElementsCalculator->createPaginationElements(
            (int)$tmdbResponse['total_results'],
            (int)floor((int)$tmdbResponse['total_results'] / (int)$tmdbResponse['total_pages']),
            $requestData->getPage(),
        );

        return Response::createJson(
            Json::encode([
                'results' => $this->historyResponseMapper->mapMovieSearchResults($tmdbResponse),
                'currentPage' => $paginationElements->getCurrentPage(),
                'maxPage' => $paginationElements->getMaxPage(),
            ]),
        );
    }
}
