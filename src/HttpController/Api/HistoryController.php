<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\HttpController\Api\RequestMapper\HistoryRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\ResponseMapper\HistoryResponseMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\Date;
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
    ) {
    }

    public function addToHistory(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $historyAdditions = Json::decode($request->getBody());

        foreach ($historyAdditions as $historyAddition) {
            $this->movieApi->addPlaysForMovieOnDate(
                (int)$historyAddition['movaryId'],
                $userId,
                Date::createFromString($historyAddition['watchedAt']),
                $historyAddition['plays'] ?? 1,
                $historyAddition['position'] ?? 1,
                $historyAddition['comment'] ?? null,
            );
        }

        return Response::createNoContent();
    }

    public function deleteFromHistory(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $historyAdditions = Json::decode($request->getBody());

        foreach ($historyAdditions as $historyAddition) {
            $this->movieApi->deleteHistoryByIdAndDate(
                (int)$historyAddition['movaryId'],
                $userId,
                Date::createFromString($historyAddition['watchedAt']),
            );
        }

        return Response::createNoContent();
    }

    public function getHistory(Request $request) : Response
    {
        $requestData = $this->historyRequestMapper->mapRequest($request);

        $historyEntries = $this->movieHistoryApi->fetchHistoryPaginated(
            $requestData->getRequestedUserId(),
            $requestData->getLimit(),
            $requestData->getPage(),
            $requestData->getSearchTerm(),
            $requestData->getSortOrder(),
        );

        $historyCount = $this->movieHistoryApi->fetchHistoryCount(
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
        $historyAdditions = Json::decode($request->getBody());

        foreach ($historyAdditions as $historyAddition) {
            $this->movieApi->replaceHistoryForMovieByDate(
                (int)$historyAddition['movaryId'],
                $userId,
                Date::createFromString($historyAddition['watchedAt']),
                $historyAddition['plays'],
                $historyAddition['position'],
                $historyAddition['comment'],
            );
        }

        return Response::createNoContent();
    }
}
