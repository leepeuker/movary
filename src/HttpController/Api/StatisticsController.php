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

readonly class StatisticsController
{
    public function __construct(
        private MovieHistoryApi $movieHistoryApi,
        private MovieApi $movieApi,
        private MovieWatchlistApi $movieWatchlistApi,
        private UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private DashboardFactory $dashboardFactory,
        private UserApi $userApi,
    ) {
    }

    // phpcs:ignore
    public function getDashboardData(Request $request) : Response
    {
        $requestedUser = $this->userApi->findUserByName((string)$request->getRouteParameters()['username']);
        if ($requestedUser === null) {
            return Response::createNotFound();
        }
        $userId = $requestedUser->getId();

        $dashboardRows = $this->dashboardFactory->createDashboardRowsForUser($requestedUser);

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
            if($row->isExtended() && $row->isVisible()) {
                if($row->isLastPlays()) {
                    $response['lastPlays'] = $this->movieHistoryApi->fetchLastPlays($userId);
                } elseif($row->isMostWatchedActors()) {
                    $response['mostWatchedActors'] = $this->movieHistoryApi->fetchActors($userId, 6, 1, gender: Gender::createMale(), personFilterUserId: $userId);
                } elseif($row->isMostWatchedActresses()) {
                    $response['mostWatchedActresses'] = $this->movieHistoryApi->fetchActors($userId, 6, 1, gender: Gender::createFemale(), personFilterUserId: $userId);
                } elseif($row->isMostWatchedDirectors()) {
                    $response['mostWatchedDirectors'] = $this->movieHistoryApi->fetchDirectors($userId, 6, 1, personFilterUserId: $userId);
                } elseif($row->isMostWatchedLanguages()) {
                    $response['mostWatchedLanguages'] = $this->movieHistoryApi->fetchMostWatchedLanguages($userId);
                } elseif($row->isMostWatchedGenres()) {
                    $response['mostWatchedGenres'] = $this->movieHistoryApi->fetchMostWatchedGenres($userId);
                } elseif($row->isMostWatchedProductionCompanies()) {
                    $response['mostWatchedProductionCompanies'] = $this->movieHistoryApi->fetchMostWatchedProductionCompanies($userId, 12);
                } elseif($row->isMostWatchedReleaseYears()) {
                    $response['mostWatchedReleaseYears'] = $this->movieHistoryApi->fetchMostWatchedReleaseYears($userId);
                } elseif($row->isWatchlist()) {
                    $response['watchlistItems'] = $this->movieWatchlistApi->fetchWatchlistPaginated($userId, 6, 1);
                }
            }
        }
        return Response::createJson(Json::encode($response));
    }

    // phpcs:ignore
    public function getStatistic(Request $request) : Response
    {
        $requestedUser = $this->userApi->findUserByName((string)$request->getRouteParameters()['username']);
        if ($requestedUser === null) {
            return Response::createNotFound();
        }
        $userId = $requestedUser->getId();
        $requestedStatistic = strtolower($request->getRouteParameters()['statistic'] ?? '');
        $response = null;
        switch($requestedStatistic) {
            case 'lastplays':
                $response = $this->movieHistoryApi->fetchLastPlays($userId);
                break;
            case 'mostwatchedactors':
                $response = $this->movieHistoryApi->fetchActors($userId, 6, 1, gender: Gender::createMale(), personFilterUserId: $userId);
                break;
            case 'mostwatchedactresses':
                $response = $this->movieHistoryApi->fetchActors($userId, 6, 1, gender: Gender::createFemale(), personFilterUserId: $userId);
                break;
            case 'mostwatcheddirectors':
                $response = $this->movieHistoryApi->fetchDirectors($userId, 6, 1, personFilterUserId: $userId);
                break;
            case 'mostwatchedlanguages':
                $response = $this->movieHistoryApi->fetchMostWatchedLanguages($userId);
                break;
            case 'mostwatchedgenres':
                $response = $this->movieHistoryApi->fetchMostWatchedGenres($userId);
                break;
            case 'mostwatchedproductioncompanies':
                $response = $this->movieHistoryApi->fetchMostWatchedProductionCompanies($userId, 12);
                break;
            case 'mostwatchedreleaseyears':
                $response = $this->movieHistoryApi->fetchMostWatchedReleaseYears($userId);
                break;
            case 'watchlist':
                $response = $this->movieWatchlistApi->fetchWatchlistPaginated($userId, 6, 1);
                break;
        }
        if($response === null) {
            return Response::createNotFound();
        }
        return Response::createJson(Json::encode($response));
    }
}
