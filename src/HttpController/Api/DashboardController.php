<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Domain\User\UserApi;
use Movary\Service\Dashboard\DashboardFactory;
use Movary\Util\Json;
use Movary\ValueObject\Gender;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class DashboardController
{
    public function __construct(
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly MovieApi $movieApi,
        private readonly MovieWatchlistApi $movieWatchlistApi,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private readonly DashboardFactory $dashboardFactory,
        private readonly UserApi $userApi,
        private readonly Authentication $authenticationService,
    ) {
    }

    public function getDashboardData(Request $request) : Response
    {
        $userId = $this->userPageAuthorizationChecker->findUserIdIfCurrentVisitorIsAllowedToSeeUser((string)$request->getRouteParameters()['username']);
        if ($userId === null) {
            return Response::createForbiddenRedirect($request->getPath());
        }

        $currentUserId = null;
        if ($this->authenticationService->isUserAuthenticated() === true) {
            $currentUserId = $this->authenticationService->getCurrentUserId();
        }

        $dashboardRows = $this->dashboardFactory->createDashboardRowsForUser($this->userApi->fetchUser($userId));

        $response = [
            'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor(),
            'totalPlayCount' => $this->movieApi->fetchTotalPlayCount($userId),
            'uniqueMoviesCount' => $this->movieApi->fetchTotalPlayCountUnique($userId),
            'totalHoursWatched' => $this->movieHistoryApi->fetchTotalHoursWatched($userId),
            'averagePersonalRating' => $this->movieHistoryApi->fetchAveragePersonalRating($userId),
            'averagePlaysPerDay' => $this->movieHistoryApi->fetchAveragePlaysPerDay($userId),
            'averageRuntime' => $this->movieHistoryApi->fetchAverageRuntime($userId),
            'dashboardRows' => $dashboardRows->asArray(),
            'lastPlays' => [],
            'mostWatchedActors' => [],
            'mostWatchedActresses' => [],
            'mostWatchedDirectors' => [],
            'mostWatchedLanguages' => [],
            'mostWatchedGenres' => [],
            'mostWatchedProductionCompanies' => [],
            'mostWatchedReleaseYears' => [],
            'watchlistItems' => [],
        ];

        foreach($dashboardRows as $row) {
            if($row->isExtended()) {
                if($row->isLastPlays()) {
                    $response['lastPlays'] = $this->movieHistoryApi->fetchLastPlays($userId);
                } else if($row->isMostWatchedActors()) {
                    $response['mostWatchedActors'] = $this->movieHistoryApi->fetchActors($userId, 6, 1, gender: Gender::createMale(), personFilterUserId: $currentUserId);
                } else if($row->isMostWatchedActresses()) {
                    $response['mostWatchedActresses'] = $this->movieHistoryApi->fetchActors($userId, 6, 1, gender: Gender::createFemale(), personFilterUserId: $currentUserId);
                } else if($row->isMostWatchedDirectors()) {
                    $response['mostWatchedDirectors'] = $this->movieHistoryApi->fetchDirectors($userId, 6, 1, personFilterUserId: $currentUserId);
                } else if($row->isMostWatchedLanguages()) {
                    $response['mostWatchedLanguages'] = $this->movieHistoryApi->fetchMostWatchedLanguages($userId);
                } else if($row->isMostWatchedGenres()) {
                    $response['mostWatchedGenres'] = $this->movieHistoryApi->fetchMostWatchedGenres($userId);
                } else if($row->isMostWatchedProductionCompanies()) {
                    $response['mostWatchedProductionCompanies'] = $this->movieHistoryApi->fetchMostWatchedProductionCompanies($userId, 12);
                } else if($row->isMostWatchedReleaseYears()) {
                    $response['mostWatchedReleaseYears'] = $this->movieHistoryApi->fetchMostWatchedReleaseYears($userId);
                } else if($row->isWatchlist()) {
                    $response['watchlistItems'] = $this->movieWatchlistApi->fetchWatchlistPaginated($userId, 6, 1);
                }
            }
        }
        return Response::createJson(Json::encode($response));
    }
}
