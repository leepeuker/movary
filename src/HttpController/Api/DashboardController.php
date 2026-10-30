<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\Dto\DashboardRowManifestDto;
use Movary\HttpController\Api\RequestMapper\DashboardRowsRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\ResponseMapper\DashboardRowResponseMapper;
use Movary\Service\Dashboard\DashboardFactory;
use Movary\Service\Dashboard\DashboardRowDataProvider;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class DashboardController
{
    public function __construct(
        private readonly RequestMapper $requestMapper,
        private readonly DashboardFactory $dashboardFactory,
        private readonly DashboardRowDataProvider $dashboardRowDataProvider,
        private readonly DashboardRowResponseMapper $responseMapper,
        private readonly Authentication $authentication,
        private readonly MovieApi $movieApi,
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly UserApi $userApi,
        private readonly DashboardRowsRequestMapper $dashboardRowsRequestMapper,
    ) {
    }

    public function getOverview(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();

        return Response::createSensitiveJson(Json::encode([
            'totalPlayCount' => $this->movieApi->fetchTotalPlayCount($userId),
            'uniqueMoviesCount' => $this->movieApi->fetchTotalPlayCountUnique($userId),
            'totalHoursWatched' => $this->movieHistoryApi->fetchTotalHoursWatched($userId),
            'averagePlaysPerDay' => $this->movieHistoryApi->fetchAveragePlaysPerDay($userId),
            'averageRuntime' => $this->movieHistoryApi->fetchAverageRuntime($userId),
            'averagePersonalRating' => $this->movieHistoryApi->fetchAveragePersonalRating($userId),
        ]));
    }

    public function getRows(Request $request) : Response
    {
        $user = $this->requestMapper->mapUsernameFromRoute($request);
        $username = (string)$request->getRouteParameters()['username'];
        $rows = [];

        foreach ($this->dashboardFactory->createDashboardRowsForUser($user) as $row) {
            $rows[] = new DashboardRowManifestDto(
                $row->getKey(),
                $row->getName(),
                count($rows),
                $row->isVisible(),
                $row->isExtended(),
                '/api/v1/users/' . $username . '/dashboard/rows/' . $row->getKey(),
            );
        }

        return Response::createSensitiveJson(Json::encode(['rows' => $rows]));
    }

    public function getRow(Request $request) : Response
    {
        $user = $this->requestMapper->mapUsernameFromRoute($request);
        $rows = $this->dashboardFactory->createDashboardRowsForUser($user);
        $row = $rows->findByKey((string)$request->getRouteParameters()['rowKey']);

        if ($row === null || $row->isVisible() === false) {
            return Response::createNotFound();
        }

        $viewerUserId = $this->authentication->authenticateApiToken($request)?->getUserId();
        $data = $this->dashboardRowDataProvider->fetch($row, $user->getId(), $viewerUserId);

        return Response::createSensitiveJson(Json::encode($this->responseMapper->map($row, $data)));
    }

    public function updateRows(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $rows = $this->dashboardRowsRequestMapper->mapRows($request);

        $this->userApi->updateVisibleDashboardRows($userId, $rows['visibleRows']);
        $this->userApi->updateExtendedDashboardRows($userId, $rows['extendedRows']);
        $this->userApi->updateOrderDashboardRows($userId, $rows['orderRows']);

        return Response::createNoContent();
    }

    public function resetRows(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();

        $this->userApi->updateVisibleDashboardRows($userId, null);
        $this->userApi->updateExtendedDashboardRows($userId, null);
        $this->userApi->updateOrderDashboardRows($userId, null);

        return Response::createNoContent();
    }
}
