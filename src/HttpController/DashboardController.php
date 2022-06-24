<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie\Api;
use Movary\Application\Movie\History\Service\Select;
use Movary\ValueObject\Gender;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class DashboardController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Select $movieHistorySelectService,
        private readonly Api $movieApi,
    ) {
    }

    public function render() : Response
    {
        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/dashboard.html.twig', [
                'totalPlayCount' => $this->movieApi->fetchHistoryCount(),
                'uniqueMoviesCount' => $this->movieApi->fetchHistoryCountUnique(),
                'totalHoursWatched' => $this->movieHistorySelectService->fetchTotalHoursWatched(),
                'averagePersonalRating' => $this->movieHistorySelectService->fetchAveragePersonalRating(),
                'averagePlaysPerDay' => $this->movieHistorySelectService->fetchAveragePlaysPerDay(),
                'averageRuntime' => $this->movieHistorySelectService->fetchAverageRuntime(),
                'firstDiaryEntry' => $this->movieHistorySelectService->fetchFirstHistoryWatchDate(),
                'lastPlays' => $this->movieHistorySelectService->fetchLastPlays(),
                'mostWatchedActors' => $this->movieHistorySelectService->fetchMostWatchedActors(1, 6, Gender::createMale()),
                'mostWatchedActresses' => $this->movieHistorySelectService->fetchMostWatchedActors(1, 6, Gender::createFemale()),
                'mostWatchedDirectors' => $this->movieHistorySelectService->fetchMostWatchedDirectors(1, 6),
                'mostWatchedLanguages' => $this->movieHistorySelectService->fetchMostWatchedLanguages(),
                'mostWatchedGenres' => $this->movieHistorySelectService->fetchMostWatchedGenres(),
                'mostWatchedProductionCompanies' => $this->movieHistorySelectService->fetchMostWatchedProductionCompanies(12),
                'mostWatchedReleaseYears' => $this->movieHistorySelectService->fetchMostWatchedReleaseYears(),
            ]),
        );
    }
}
