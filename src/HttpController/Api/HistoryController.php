<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class HistoryController
{
    private const DEFAULT_LIMIT = 24;

    public function __construct(
        private readonly UserApi $userApi,
        private readonly Authentication $authenticationService,
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
    ) {
    }

    public function getHistory(Request $request) : Response
    {
        $requestedUser = $this->userApi->findUserByName((string)$request->getRouteParameters()['username']);
        if ($requestedUser === null) {
            return Response::createNotFound();
        }

        if ($this->authenticationService->isUserPageVisibleForApiRequest($request, $requestedUser) === false) {
            return Response::createForbidden();
        }

        $searchTerm = $request->getGetParameters()['search'] ?? null;
        $page = $request->getGetParameters()['page'] ?? 1;
        $limit = self::DEFAULT_LIMIT;

        $historyPaginated = $this->movieHistoryApi->fetchHistoryPaginated($requestedUser->getId(), $limit, (int)$page, $searchTerm);
        $historyCount = $this->movieHistoryApi->fetchHistoryCount($requestedUser->getId(), $searchTerm);

        $paginationElements = $this->paginationElementsCalculator->createPaginationElements($historyCount, $limit, (int)$page);

        return Response::createJson(
            Json::encode([
                'movies' => $historyPaginated,
                'currentPage' => $paginationElements->getCurrentPage(),
                'maxPage' => $paginationElements->getMaxPage(),
            ]),
        );
    }
}
