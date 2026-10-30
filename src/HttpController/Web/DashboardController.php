<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Domain\User\UserApi;
use Movary\Service\Dashboard\DashboardFactory;
use Movary\Service\Dashboard\DashboardRowDataProvider;
use Movary\Service\Dashboard\Dto\DashboardRowList;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class DashboardController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly MovieApi $movieApi,
        private readonly DashboardRowDataProvider $dashboardRowDataProvider,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private readonly DashboardFactory $dashboardFactory,
        private readonly UserApi $userApi,
        private readonly Authentication $authenticationService,
    ) {
    }

    public function redirectToDashboard(Request $request) : Response
    {
        $requestedUserName = (string)$request->getRouteParameters()['username'];

        return Response::createMovedPermanently('/users/' . $requestedUserName);
    }

    public function render(Request $request) : Response
    {
        $requestedUserId = $this->userApi->fetchUserByName((string)$request->getRouteParameters()['username'])->getId();

        $currentUserId = $this->authenticationService->authenticateWebSession()?->getUserId();

        $dashboardRows = $this->dashboardFactory->createDashboardRowsForUser($this->userApi->fetchUser($requestedUserId));

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render(
                'page/dashboard.html.twig',
                array_merge(
                    [
                        'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor(),
                        'totalPlayCount' => $this->movieApi->fetchTotalPlayCount($requestedUserId),
                        'uniqueMoviesCount' => $this->movieApi->fetchTotalPlayCountUnique($requestedUserId),
                        'totalHoursWatched' => $this->movieHistoryApi->fetchTotalHoursWatched($requestedUserId),
                        'averagePersonalRating' => $this->movieHistoryApi->fetchAveragePersonalRating($requestedUserId),
                        'averagePlaysPerDay' => $this->movieHistoryApi->fetchAveragePlaysPerDay($requestedUserId),
                        'averageRuntime' => $this->movieHistoryApi->fetchAverageRuntime($requestedUserId),
                        'dashboardRows' => $dashboardRows,
                    ],
                    $this->fetchExtendedDashboardRowData($dashboardRows, $requestedUserId, $currentUserId),
                ),
            ),
        );
    }

    public function renderRow(Request $request) : Response
    {
        $routeParameters = $request->getRouteParameters();
        $requestedUserId = $this->userApi->fetchUserByName((string)$routeParameters['username'])->getId();
        $requestedRowId = (int)$routeParameters['rowId'];
        $dashboardRows = $this->dashboardFactory->createDashboardRowsForUser($this->userApi->fetchUser($requestedUserId));

        $requestedRow = null;
        foreach ($dashboardRows as $dashboardRow) {
            if ($dashboardRow->getId() === $requestedRowId) {
                $requestedRow = $dashboardRow;
                break;
            }
        }

        if ($requestedRow === null || $requestedRow->isVisible() === false) {
            return Response::createNotFound();
        }

        $currentUserId = $this->authenticationService->authenticateWebSession()?->getUserId();

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render(
                'component/dashboard/row.html.twig',
                array_merge(
                    ['dashboardRow' => $requestedRow],
                    $this->dashboardRowDataProvider->fetch($requestedRow, $requestedUserId, $currentUserId),
                ),
            ),
        );
    }

    private function fetchExtendedDashboardRowData(
        DashboardRowList $dashboardRows,
        int $requestedUserId,
        ?int $currentUserId,
    ) : array {
        $renderData = [];

        foreach ($dashboardRows as $row) {
            if ($row->isVisible() === false || $row->isExtended() === false) {
                continue;
            }

            $renderData = array_merge(
                $renderData,
                $this->dashboardRowDataProvider->fetch($row, $requestedUserId, $currentUserId),
            );
        }

        return $renderData;
    }
}
