<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Domain\User\UserApi;
use Movary\Service\Dashboard\DashboardFactory;
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

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/dashboard.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor($request),
                'totalPlayCount' => $this->movieApi->fetchTotalPlayCount($userId),
                'uniqueMoviesCount' => $this->movieApi->fetchTotalPlayCountUnique($userId),
                'totalHoursWatched' => $this->movieHistoryApi->fetchTotalHoursWatched($userId),
                'averagePersonalRating' => $this->movieHistoryApi->fetchAveragePersonalRating($userId),
                'averagePlaysPerDay' => $this->movieHistoryApi->fetchAveragePlaysPerDay($userId),
                'averageRuntime' => $this->movieHistoryApi->fetchAverageRuntime($userId),
                'firstDiaryEntry' => $this->movieHistoryApi->fetchFirstHistoryWatchDate($userId),
                'lastPlays' => $this->movieHistoryApi->fetchLastPlays($userId),
                'mostWatchedActors' => $this->movieHistoryApi->fetchActors($userId, 6, 1, gender: Gender::createMale(), personFilterUserId: $currentUserId),
                'mostWatchedActresses' => $this->movieHistoryApi->fetchActors($userId, 6, 1, gender: Gender::createFemale(), personFilterUserId: $currentUserId),
                'mostWatchedDirectors' => $this->movieHistoryApi->fetchDirectors($userId, 6, 1, personFilterUserId: $currentUserId),
                'mostWatchedLanguages' => $this->movieHistoryApi->fetchMostWatchedLanguages($userId),
                'mostWatchedGenres' => $this->movieHistoryApi->fetchMostWatchedGenres($userId),
                'mostWatchedProductionCompanies' => $this->movieHistoryApi->fetchMostWatchedProductionCompanies($userId, 12),
                'mostWatchedReleaseYears' => $this->movieHistoryApi->fetchMostWatchedReleaseYears($userId),
                'watchlistItems' => $this->movieWatchlistApi->fetchWatchlistPaginated($userId, 6, 1),
                'dashboardRows' => $dashboardRows,
            ]),
        );
    }
}
