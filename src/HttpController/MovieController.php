<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie\History\Service\Select;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class MovieController
{
    public function __construct(
        private readonly Select $movieHistorySelectService,
        private readonly Environment $twig
    ) {
    }

    public function renderPage(Request $request) : Response
    {
        $movieId = (int)$request->getRouteParameters()['id'];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/movie.html.twig', [
                'watchDates' => $this->movieHistorySelectService->fetchHistoryByMovieId($movieId),
            ]),
        );
    }
}
