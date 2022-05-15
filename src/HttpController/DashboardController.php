<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie\History\Service\Select;
use Movary\ValueObject\Gender;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class DashboardController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Select $movieHistorySelectService
    ) {
    }

    public function render() : Response
    {
        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('dashboard.html.twig', [
                'totalPlayCount' => $this->movieHistorySelectService->fetchHistoryCount(),
                'uniqueMoviesCount' => $this->movieHistorySelectService->fetchUniqueMovieInHistoryCount(),
                'totalHoursWatched' => $this->movieHistorySelectService->fetchTotalHoursWatched(),
                'average10Rating' => $this->movieHistorySelectService->fetchAverage10Rating(),
                'averagePlaysPerDay' => $this->movieHistorySelectService->fetchAveragePlaysPerDay(),
                'averageRuntime' => $this->movieHistorySelectService->fetchAverageRuntime(),
                'firstDiaryEntry' => $this->movieHistorySelectService->fetchFirstHistoryWatchDate(),
                'lastPlays' => $this->movieHistorySelectService->fetchLastPlays(),
                'topActors' => $this->movieHistorySelectService->fetchMostWatchedActors(6, Gender::createMale()),
                'topActresses' => $this->movieHistorySelectService->fetchMostWatchedActors(6, Gender::createFemale()),
            ]),
        );
    }
}
