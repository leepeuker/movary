<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Domain\User\UserApi;
use Movary\Service\Dashboard\DashboardFactory;
use Movary\Service\Dashboard\Dto\DashboardRow;
use Movary\Service\Dashboard\Dto\DashboardRowList;
use Movary\ValueObject\Gender;
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
        private readonly MovieWatchlistApi $movieWatchlistApi,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private readonly DashboardFactory $dashboardFactory,
        private readonly UserApi $userApi,
        private readonly Authentication $authenticationService,
    ) {
    }

    public function render(Request $request) : Response
    {
        $requestedUserId = $this->userApi->fetchUserByName((string)$request->getRouteParameters()['username'])->getId();

        $currentUserId = null;
        if ($this->authenticationService->isUserAuthenticatedWithCookie() === true) {
            $currentUserId = $this->authenticationService->getCurrentUserId();
        }

        $dashboardRows = $this->dashboardFactory->createDashboardRowsForUser($this->userApi->fetchUser($requestedUserId));

        $renderData = array_merge(
            [
                'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor(),
                'totalPlayCount' => $this->movieApi->fetchTotalPlayCount($requestedUserId),
                'uniqueMoviesCount' => $this->movieApi->fetchTotalPlayCountUnique($requestedUserId),
                'totalHoursWatched' => $this->movieHistoryApi->fetchTotalHoursWatched($requestedUserId),
                'averagePersonalRating' => $this->movieHistoryApi->fetchAveragePersonalRating($requestedUserId),
                'averagePlaysPerDay' => $this->movieHistoryApi->fetchAveragePlaysPerDay($requestedUserId),
                'averageRuntime' => $this->movieHistoryApi->fetchAverageRuntime($requestedUserId),
                'firstDiaryEntry' => $this->movieHistoryApi->fetchFirstHistoryWatchDate($requestedUserId),
                'dashboardRows' => $dashboardRows,
            ],
            $this->fetchVisibleDashboardRowData($dashboardRows, $requestedUserId, $currentUserId),
        );

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/dashboard.html.twig', $renderData),
        );
    }

    private function fetchVisibleDashboardRowData(DashboardRowList $dashboardRows, int $requestedUserId, ?int $currentUserId) : array
    {
        $renderData = [];

        foreach ($dashboardRows as $row) {
            if ($row->isVisible() === false) {
                continue;
            }

            $renderData = array_merge(
                $renderData,
                $this->fetchDashboardRowData($row, $requestedUserId, $currentUserId),
            );
        }

        return $renderData;
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    private function fetchDashboardRowData(DashboardRow $row, int $requestedUserId, ?int $currentUserId) : array
    {
        return match (true) {
            $row->isLastPlays() => ['lastPlays' => $this->movieHistoryApi->fetchLastPlays($requestedUserId)],
            $row->isLastPlaysCinema() => ['lastPlaysCinema' => $this->movieHistoryApi->fetchLastPlaysCinema($requestedUserId)],
            $row->isMostWatchedActors() => ['mostWatchedActors' => $this->movieHistoryApi->fetchActors($requestedUserId, 6, 1, gender: Gender::createMale(), personFilterUserId: $currentUserId)],
            $row->isMostWatchedActresses() => ['mostWatchedActresses' => $this->movieHistoryApi->fetchActors($requestedUserId, 6, 1, gender: Gender::createFemale(), personFilterUserId: $currentUserId)],
            $row->isMostWatchedDirectors() => ['mostWatchedDirectors' => $this->movieHistoryApi->fetchDirectors($requestedUserId, 6, 1, personFilterUserId: $currentUserId)],
            $row->isMostWatchedLanguages() => ['mostWatchedLanguages' => $this->movieHistoryApi->fetchMostWatchedLanguages($requestedUserId)],
            $row->isMostWatchedGenres() => ['mostWatchedGenres' => $this->movieHistoryApi->fetchMostWatchedGenres($requestedUserId)],
            $row->isMostWatchedProductionCompanies() => ['mostWatchedProductionCompanies' => $this->movieHistoryApi->fetchMostWatchedProductionCompanies($requestedUserId, 12)],
            $row->isMostWatchedReleaseYears() => ['mostWatchedReleaseYears' => $this->movieHistoryApi->fetchMostWatchedReleaseYears($requestedUserId)],
            $row->isWatchlist() => ['watchlistItems' => $this->movieWatchlistApi->fetchWatchlistPaginated($requestedUserId, 6, 1)],
            $row->isTopLocations() => ['topLocations' => $this->movieHistoryApi->fetchTopLocations($requestedUserId)],
            default => [],
        };
    }
}
