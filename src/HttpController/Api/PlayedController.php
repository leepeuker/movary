<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\HttpController\Api\RequestMapper\PlayedRequestMapper;
use Movary\HttpController\Api\ResponseMapper\PlayedResponseMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class PlayedController
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
        private readonly PlayedRequestMapper $playedRequestMapper,
        private readonly PlayedResponseMapper $playedResponseMapper,
    ) {
    }

    public function getPlayed(Request $request) : Response
    {
        $requestData = $this->playedRequestMapper->mapRequest($request);

        $playedEntries = $this->movieApi->fetchPlayedMoviesPaginated(
            $requestData->getRequestedUserId(),
            $requestData->getLimit(),
            $requestData->getPage(),
            $requestData->getSearchTerm(),
            $requestData->getSortBy(),
            $requestData->getSortOrder(),
            $requestData->getReleaseYear(),
            $requestData->getLanguage(),
            $requestData->getGenre(),
            null,
            null,
            null,
        );

        $watchDates = $this->movieApi->fetchWatchDatesForMovies($requestData->getRequestedUserId(), $playedEntries);

        $watchlistCount = $this->movieApi->fetchPlayedMoviesCount(
            $requestData->getRequestedUserId(),
            $requestData->getSearchTerm(),
            $requestData->getReleaseYear(),
            $requestData->getLanguage(),
            $requestData->getGenre(),
            null,
            null,
            null,
        );

        $paginationElements = $this->paginationElementsCalculator->createPaginationElements(
            $watchlistCount,
            $requestData->getLimit(),
            $requestData->getPage(),
        );

        return Response::createJson(
            Json::encode([
                'played' => $this->playedResponseMapper->mapPlayedEntries($playedEntries, $watchDates),
                'currentPage' => $paginationElements->getCurrentPage(),
                'maxPage' => $paginationElements->getMaxPage(),
            ]),
        );
    }
}
