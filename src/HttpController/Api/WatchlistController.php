<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\HttpController\Api\RequestMapper\WatchlistRequestMapper;
use Movary\HttpController\Api\ResponseMapper\WatchlistResponseMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class WatchlistController
{
    public function __construct(
        private readonly MovieWatchlistApi $movieWatchlistApi,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
        private readonly WatchlistRequestMapper $watchlistRequestMapper,
        private readonly WatchlistResponseMapper $watchlistResponseMapper,
    ) {
    }

    public function addToWatchlist(Request $request) : Response
    {
        return Response::createNoContent();
    }

    public function deleteFromWatchlist(Request $request) : Response
    {
        return Response::createNoContent();
    }

    public function getWatchlist(Request $request) : Response
    {
        $requestData = $this->watchlistRequestMapper->mapRequest($request);

        $watchlistEntries = $this->movieWatchlistApi->fetchWatchlistPaginated(
            $requestData->getRequestedUserId(),
            $requestData->getLimit(),
            $requestData->getPage(),
            $requestData->getSearchTerm(),
            $requestData->getSortBy(),
            $requestData->getSortOrder(),
            $requestData->getReleaseYear(),
            $requestData->getLanguage(),
            $requestData->getGenre(),
        );

        $watchlistCount = $this->movieWatchlistApi->fetchWatchlistCount(
            $requestData->getRequestedUserId(),
            $requestData->getSearchTerm(),
            $requestData->getReleaseYear(),
            $requestData->getLanguage(),
            $requestData->getGenre(),
        );

        $paginationElements = $this->paginationElementsCalculator->createPaginationElements(
            $watchlistCount,
            $requestData->getLimit(),
            $requestData->getPage(),
        );

        return Response::createJson(
            Json::encode([
                'watchlist' => $this->watchlistResponseMapper->mapWatchlistEntries($watchlistEntries),
                'currentPage' => $paginationElements->getCurrentPage(),
                'maxPage' => $paginationElements->getMaxPage(),
            ]),
        );
    }
}
