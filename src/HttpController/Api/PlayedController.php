<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\HttpController\Api\RequestMapper\PlayedRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\ResponseMapper\PlayedResponseMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\Date;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class PlayedController
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
        private readonly PlayedRequestMapper $playedRequestMapper,
        private readonly PlayedResponseMapper $playedResponseMapper,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    public function addToPlayed(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $watchlistAdditions = Json::decode($request->getBody());

        // TODO add movie plays

        return Response::createNoContent();
    }

    public function deleteFromPlayed(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $historyAdditions = Json::decode($request->getBody());

        foreach ($historyAdditions as $historyAddition) {
            $this->movieApi->deleteHistoryByIdAndDate(
                (int)$historyAddition['movaryId'],
                $userId,
                isset($historyAddition['watchedAt']) === true ? Date::createFromString($historyAddition['watchedAt']) : null,
            );
        }

        return Response::createNoContent();
    }

    public function getPlayed(Request $request) : Response
    {
        $requestData = $this->playedRequestMapper->mapRequest($request);

        $watchlistEntries = $this->movieApi->fetchPlayedMoviesPaginated(
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

        $watchlistCount = $this->movieApi->fetchPlayedMoviesCount(
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
                'played' => $this->playedResponseMapper->mapPlayedEntries($watchlistEntries),
                'currentPage' => $paginationElements->getCurrentPage(),
                'maxPage' => $paginationElements->getMaxPage(),
            ]),
        );
    }

    public function updatePlayed(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $historyAdditions = Json::decode($request->getBody());

        foreach ($historyAdditions as $historyAddition) {
            $this->movieApi->replaceHistoryForMovieByDate(
                (int)$historyAddition['movaryId'],
                $userId,
                isset($historyAddition['watchedAt']) === true ? Date::createFromString($historyAddition['watchedAt']) : null,
                $historyAddition['plays'],
                $historyAddition['comment'],
            );
        }

        return Response::createNoContent();
    }
}
