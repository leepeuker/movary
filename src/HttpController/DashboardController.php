<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
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
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private readonly DashboardFactory $dashboardFactory,
        private readonly UserApi $userApi,
    ) {
    }

    public function redirectToDashboard(Request $request) : Response
    {
        $user = $this->userPageAuthorizationChecker->findUserIfCurrentVisitorIsAllowedToSeeUser((string)$request->getRouteParameters()['username']);
        if ($user === null) {
            return Response::createSeeOther('/');
        }

        return Response::createSeeOther('/users/' . $user->getName() . '/dashboard');
    }

    public function render(Request $request) : Response
    {
        $userId = $this->userPageAuthorizationChecker->findUserIdIfCurrentVisitorIsAllowedToSeeUser((string)$request->getRouteParameters()['username']);
        if ($userId === null) {
            return Response::createSeeOther('/');
        }

        $dashboardRows = $this->dashboardFactory->createDashboardRowsForUser($this->userApi->fetchUser($userId));

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/dashboard.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor(),
                'totalPlayCount' => $this->movieApi->fetchHistoryCount($userId),
                'uniqueMoviesCount' => $this->movieApi->fetchHistoryCountUnique($userId),
                'totalHoursWatched' => $this->movieHistoryApi->fetchTotalHoursWatched($userId),
                'averagePersonalRating' => $this->movieHistoryApi->fetchAveragePersonalRating($userId),
                'averagePlaysPerDay' => $this->movieHistoryApi->fetchAveragePlaysPerDay($userId),
                'averageRuntime' => $this->movieHistoryApi->fetchAverageRuntime($userId),
                'firstDiaryEntry' => $this->movieHistoryApi->fetchFirstHistoryWatchDate($userId),
                'lastPlays' => $this->movieHistoryApi->fetchLastPlays($userId),
                'mostWatchedActors' => $this->movieHistoryApi->fetchActors($userId, 6, 1, gender: Gender::createMale()),
                'mostWatchedActresses' => $this->movieHistoryApi->fetchActors($userId, 6, 1, gender: Gender::createFemale()),
                'mostWatchedDirectors' => $this->movieHistoryApi->fetchDirectors($userId, 6, 1),
                'mostWatchedLanguages' => $this->movieHistoryApi->fetchMostWatchedLanguages($userId),
                'mostWatchedGenres' => $this->movieHistoryApi->fetchMostWatchedGenres($userId),
                'mostWatchedProductionCompanies' => $this->movieHistoryApi->fetchMostWatchedProductionCompanies($userId, 12),
                'mostWatchedReleaseYears' => $this->movieHistoryApi->fetchMostWatchedReleaseYears($userId),
                'dashboardRows' => $dashboardRows,
            ]),
        );
    }
}
