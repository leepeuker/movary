<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie\History\Service\Select;
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
                'watchedMoviesCount' => $this->movieHistorySelectService->fetchHistoryCount(),
                'uniqueWatchedMoviesCount' => $this->movieHistorySelectService->fetchUniqueMovieInHistoryCount(),
                'firstDiaryEntry' => $this->movieHistorySelectService->fetchFirstHistoryWatchDate(),
            ]),
        );
    }
}
