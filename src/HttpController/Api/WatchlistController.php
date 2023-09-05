<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\RequestMapper\WatchlistRequestMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class WatchlistController
{
    public function __construct(
        private readonly MovieWatchlistApi $movieWatchlistApi,
        private readonly UserApi $userApi,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
        private readonly Authentication $authenticationService,
        private readonly WatchlistRequestMapper $watchlistRequestMapper,
    ) {
    }

    public function getWatchlist(Request $request) : Response
    {
        $requestedUser = $this->userApi->findUserByName((string)$request->getRouteParameters()['username']);
        if ($requestedUser === null) {
            return Response::createNotFound();
        }

        if ($this->authenticationService->isUserPageVisibleForApiRequest($request, $requestedUser) === false) {
            return Response::createForbidden();
        }

        $requestData = $this->watchlistRequestMapper->mapRenderPageRequest($request);

        $watchlistPaginated = $this->movieWatchlistApi->fetchWatchlistPaginated(
            $requestedUser->getId(),
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
            $requestedUser->getId(),
            $requestData->getSearchTerm(),
            $requestData->getReleaseYear(),
            $requestData->getLanguage(),
            $requestData->getGenre(),
        );

        $paginationElements = $this->paginationElementsCalculator->createPaginationElements($watchlistCount, $requestData->getLimit(), $requestData->getPage());

        return Response::createJson(
            Json::encode([
                'movies' => $watchlistPaginated,
                'currentPage' => $paginationElements->getCurrentPage(),
                'maxPage' => $paginationElements->getMaxPage(),
            ]),
        );
    }
}
