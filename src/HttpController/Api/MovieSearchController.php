<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\User\Service\Authentication;
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
        private readonly Authentication $authenticationService,
    ) {
    }

    public function search(Request $request) : Response
    {
        $requestData = $this->searchRequestMapper->mapRequest($request);
        $userId = $this->authenticationService->getCurrentUserId();

        $tmdbResponse = $this->tmdbApi->searchMovie(
            $requestData->getSearchTerm(),
            $requestData->getYear(),
            $requestData->getPage(),
        );

        $totalResults = (int)$tmdbResponse['total_results'];
        if ($totalResults === 0) {
            return Response::createJson(
                Json::encode([
                    'results' => [],
                    'currentPage' => 0,
                    'maxPage' => 0,
                ]),
            );
        }

        $limit = (int)floor($totalResults / (int)$tmdbResponse['total_pages']);

        $paginationElements = $this->paginationElementsCalculator->createPaginationElements(
            $totalResults,
            $limit,
            $requestData->getPage(),
        );

        return Response::createJson(
            Json::encode([
                'results' => $this->historyResponseMapper->mapMovieSearchResults($userId, $tmdbResponse),
                'currentPage' => $paginationElements->getCurrentPage(),
                'maxPage' => $paginationElements->getMaxPage(),
            ]),
        );
    }
}
