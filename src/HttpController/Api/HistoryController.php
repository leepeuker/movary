<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\History\PlayCountConflict;
use Movary\Domain\Movie\MovieApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\HistoryRequestMapper;
use Movary\HttpController\Api\RequestMapper\PlayRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\ResponseMapper\HistoryResponseMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class HistoryController
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
        private readonly HistoryRequestMapper $historyRequestMapper,
        private readonly HistoryResponseMapper $historyResponseMapper,
        private readonly RequestMapper $requestMapper,
        private readonly PlayRequestMapper $playRequestMapper,
    ) {
    }

    public function addToHistory(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $historyAdditions = $this->historyRequestMapper->mapAdditions($request);

        foreach ($historyAdditions as $historyAddition) {
            $this->movieApi->addPlaysForMovieOnDate(
                $historyAddition['movieId'],
                $userId,
                $historyAddition['watchedAt'],
                $historyAddition['plays'],
                $historyAddition['position'],
                $historyAddition['comment'],
            );
        }

        return Response::createNoContent();
    }

    public function deleteFromHistory(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $historyAdditions = $this->historyRequestMapper->mapDeletions($request);

        foreach ($historyAdditions as $historyAddition) {
            $this->movieApi->deleteHistoryByIdAndDate(
                $historyAddition['movieId'],
                $userId,
                $historyAddition['watchedAt'],
            );
        }

        return Response::createNoContent();
    }

    public function getHistory(Request $request) : Response
    {
        $requestData = $this->historyRequestMapper->mapRequest($request);

        $historyEntries = $this->movieHistoryApi->fetchLegacyHistoryPaginated(
            $requestData->getRequestedUserId(),
            $requestData->getLimit(),
            $requestData->getPage(),
            $requestData->getSearchTerm(),
            $requestData->getSortOrder(),
        );

        $historyCount = $this->movieHistoryApi->fetchLegacyHistoryCount(
            $requestData->getRequestedUserId(),
            $requestData->getSearchTerm(),
        );

        $paginationElements = $this->paginationElementsCalculator->createPaginationElements(
            $historyCount,
            $requestData->getLimit(),
            $requestData->getPage(),
        );

        return Response::createJson(
            Json::encode([
                'history' => $this->historyResponseMapper->mapHistoryEntries($historyEntries),
                'currentPage' => $paginationElements->getCurrentPage(),
                'maxPage' => $paginationElements->getMaxPage(),
            ]),
        );
    }

    public function updateHistory(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $historyAdditions = $this->historyRequestMapper->mapUpdates($request);

        foreach ($historyAdditions as $historyAddition) {
            try {
                $this->movieApi->replaceHistoryForMovieByDate(
                    $historyAddition['movieId'],
                    $userId,
                    $historyAddition['watchedAt'],
                    $historyAddition['plays'],
                    $historyAddition['position'],
                    $historyAddition['comment'],
                    commentProvided: $historyAddition['commentProvided'],
                );
            } catch (PlayCountConflict) {
                throw InvalidApiRequest::playCountConflict();
            }
        }

        return Response::createNoContent();
    }

    public function getPlays(Request $request) : Response
    {
        $requestData = $this->historyRequestMapper->mapRequest($request);
        $userId = $requestData->getRequestedUserId();
        $plays = $this->movieHistoryApi->fetchPlayHistoryPaginated(
            $userId,
            $requestData->getLimit(),
            $requestData->getPage(),
            $requestData->getSearchTerm(),
            $requestData->getSortOrder(),
        );
        $count = $this->movieHistoryApi->fetchPlayHistoryCount($userId, $requestData->getSearchTerm());
        $pagination = $this->paginationElementsCalculator->createPaginationElements($count, $requestData->getLimit(), $requestData->getPage());

        return Response::createSensitiveJson(Json::encode([
            'plays' => $this->historyResponseMapper->mapPlayEntries($plays),
            'currentPage' => $pagination->getCurrentPage(),
            'maxPage' => $pagination->getMaxPage(),
        ]));
    }

    public function getPlay(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $play = $this->movieHistoryApi->findPlayForApi((int)$request->getRouteParameters()['playId'], $userId);
        if ($play === null) {
            return Response::createNotFound();
        }

        return Response::createSensitiveJson(Json::encode($this->historyResponseMapper->mapPlayEntry($play)));
    }

    public function updatePlay(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $playId = (int)$request->getRouteParameters()['playId'];
        if ($this->movieHistoryApi->findPlayById($playId, $userId) === null) {
            return Response::createNotFound();
        }

        $update = $this->playRequestMapper->mapUpdate($request, $userId);
        $this->movieHistoryApi->updatePlayById(
            $playId,
            $userId,
            $update['watchedAt'],
            $update['position'],
            $update['comment'],
            $update['locationId'],
        );

        return Response::createNoContent();
    }

    public function deletePlay(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $playId = (int)$request->getRouteParameters()['playId'];
        if ($this->movieHistoryApi->findPlayById($playId, $userId) === null) {
            return Response::createNotFound();
        }

        $this->movieHistoryApi->deletePlayById($playId, $userId);

        return Response::createNoContent();
    }
}
