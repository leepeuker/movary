<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\HttpController\Api\RequestMapper\HistoryRequestMapper;
use Movary\HttpController\Api\ResponseMapper\HistoryResponseMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class HistoryController
{
    public function __construct(
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
        private readonly HistoryRequestMapper $historyRequestMapper,
        private readonly HistoryResponseMapper $historyResponseMapper,
    ) {
    }

    public function getHistory(Request $request) : Response
    {
        $requestData = $this->historyRequestMapper->mapRequest($request);

        $historyEntries = $this->movieHistoryApi->fetchHistoryPaginated(
            $requestData->getRequestedUserId(),
            $requestData->getLimit(),
            $requestData->getPage(),
            $requestData->getSearchTerm(),
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
}
