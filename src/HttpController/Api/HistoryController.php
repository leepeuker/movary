<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\HttpController\Api\RequestMapper\HistoryRequestMapper;
use Movary\HttpController\Api\RequestMapper\PlayRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\ResponseMapper\HistoryResponseMapper;
use Movary\Service\ApplicationUrlService;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\RelativeUrl;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

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
        private readonly ApplicationUrlService $applicationUrlService,
    ) {
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

    public function createPlay(Request $request) : Response
    {
        $username = (string)$request->getRouteParameters()['username'];
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $creation = $this->playRequestMapper->mapCreation($request, $userId);
        $playId = $this->movieApi->createPlay(
            $creation['movieId'],
            $userId,
            $creation['watchedAt'],
            $creation['position'],
            $creation['comment'],
            $creation['locationId'],
        );
        $play = $this->movieHistoryApi->findPlayForApi($playId, $userId);
        if ($play === null) {
            throw new \RuntimeException('Created play could not be found.');
        }

        return Response::createSensitiveJson(
            Json::encode($this->historyResponseMapper->mapPlayEntry($play)),
            StatusCode::createCreated(),
            [Header::createLocation($this->applicationUrlService->createApplicationUrl(
                RelativeUrl::create('/api/v1/users/' . $username . '/plays/' . $playId),
            ))],
        );
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
