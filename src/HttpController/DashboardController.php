<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie;
use Movary\Application\Movie\History\Service\Select;
use Movary\Application\User;
use Movary\ValueObject\Gender;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class DashboardController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Select $movieHistorySelectService,
        private readonly Movie\Api $movieApi,
    ) {
    }

    public function render(Request $request) : Response
    {
        $userId = (int)$request->getRouteParameters()['userId'];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/dashboard.html.twig', [
                'totalPlayCount' => $this->movieApi->fetchHistoryCount($userId),
                'uniqueMoviesCount' => $this->movieApi->fetchHistoryCountUnique($userId),
                'totalHoursWatched' => $this->movieHistorySelectService->fetchTotalHoursWatched($userId),
                'averagePersonalRating' => $this->movieHistorySelectService->fetchAveragePersonalRating($userId),
                'averagePlaysPerDay' => $this->movieHistorySelectService->fetchAveragePlaysPerDay($userId),
                'averageRuntime' => $this->movieHistorySelectService->fetchAverageRuntime($userId),
                'firstDiaryEntry' => $this->movieHistorySelectService->fetchFirstHistoryWatchDate($userId),
                'lastPlays' => $this->movieHistorySelectService->fetchLastPlays($userId),
                'mostWatchedActors' => $this->movieHistorySelectService->fetchMostWatchedActors($userId, 1, 6, Gender::createMale()),
                'mostWatchedActresses' => $this->movieHistorySelectService->fetchMostWatchedActors($userId, 1, 6, Gender::createFemale()),
                'mostWatchedDirectors' => $this->movieHistorySelectService->fetchMostWatchedDirectors($userId, 1, 6),
                'mostWatchedLanguages' => $this->movieHistorySelectService->fetchMostWatchedLanguages($userId),
                'mostWatchedGenres' => $this->movieHistorySelectService->fetchMostWatchedGenres($userId),
                'mostWatchedProductionCompanies' => $this->movieHistorySelectService->fetchMostWatchedProductionCompanies($userId, 12),
                'mostWatchedReleaseYears' => $this->movieHistorySelectService->fetchMostWatchedReleaseYears($userId),
            ]),
        );
    }
}
