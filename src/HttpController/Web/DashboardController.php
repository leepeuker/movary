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
        $userId = $this->userApi->fetchUserByName((string)$request->getRouteParameters()['username'])->getId();

        $currentUserId = null;
        if ($this->authenticationService->isUserAuthenticatedWithCookie() === true) {
            $currentUserId = $this->authenticationService->getCurrentUserId();
        }

        $dashboardRows = $this->dashboardFactory->createDashboardRowsForUser($this->userApi->fetchUser($userId));

        $renderData = array_merge(
            [
                'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor(),
                'totalPlayCount' => $this->movieApi->fetchTotalPlayCount($userId),
                'uniqueMoviesCount' => $this->movieApi->fetchTotalPlayCountUnique($userId),
                'totalHoursWatched' => $this->movieHistoryApi->fetchTotalHoursWatched($userId),
                'averagePersonalRating' => $this->movieHistoryApi->fetchAveragePersonalRating($userId),
                'averagePlaysPerDay' => $this->movieHistoryApi->fetchAveragePlaysPerDay($userId),
                'averageRuntime' => $this->movieHistoryApi->fetchAverageRuntime($userId),
                'firstDiaryEntry' => $this->movieHistoryApi->fetchFirstHistoryWatchDate($userId),
                'dashboardRows' => $dashboardRows,
            ],
            $this->fetchVisibleDashboardRowData($dashboardRows, $userId, $currentUserId),
        );

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/dashboard.html.twig', $renderData),
        );
    }

    private function fetchVisibleDashboardRowData(DashboardRowList $dashboardRows, int $userId, ?int $currentUserId) : array
    {
        $renderData = [];

        foreach ($dashboardRows as $row) {
            if ($row->isVisible() === false) {
                continue;
            }

            $renderData = array_merge(
                $renderData,
                $this->getRowData($row, $userId, $currentUserId),
            );
        }

        return $renderData;
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    private function getRowData(DashboardRow $row, int $userId, ?int $currentUserId) : array
    {
        return match (true) {
            $row->isLastPlays() => ['lastPlays' => $this->movieHistoryApi->fetchLastPlays($userId)],
            $row->isLastPlaysCinema() => ['lastPlaysCinema' => $this->movieHistoryApi->fetchLastPlaysCinema($userId)],
            $row->isMostWatchedActors() => ['mostWatchedActors' => $this->movieHistoryApi->fetchActors($userId, 6, 1, gender: Gender::createMale(), personFilterUserId: $currentUserId)],
            $row->isMostWatchedActresses() => ['mostWatchedActresses' => $this->movieHistoryApi->fetchActors($userId, 6, 1, gender: Gender::createFemale(), personFilterUserId: $currentUserId)],
            $row->isMostWatchedDirectors() => ['mostWatchedDirectors' => $this->movieHistoryApi->fetchDirectors($userId, 6, 1, personFilterUserId: $currentUserId)],
            $row->isMostWatchedLanguages() => ['mostWatchedLanguages' => $this->movieHistoryApi->fetchMostWatchedLanguages($userId)],
            $row->isMostWatchedGenres() => ['mostWatchedGenres' => $this->movieHistoryApi->fetchMostWatchedGenres($userId)],
            $row->isMostWatchedProductionCompanies() => ['mostWatchedProductionCompanies' => $this->movieHistoryApi->fetchMostWatchedProductionCompanies($userId, 12)],
            $row->isMostWatchedReleaseYears() => ['mostWatchedReleaseYears' => $this->movieHistoryApi->fetchMostWatchedReleaseYears($userId)],
            $row->isWatchlist() => ['watchlistItems' => $this->movieWatchlistApi->fetchWatchlistPaginated($userId, 6, 1)],
            $row->isTopLocations() => ['topLocations' => $this->movieHistoryApi->fetchTopLocations($userId)],
            default => [],
        };
    }
}
