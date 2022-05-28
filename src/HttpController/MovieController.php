<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie;
use Movary\Application\Movie\History;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class MovieController
{
    public function __construct(
        private readonly History\Service\Select $movieHistorySelectService,
        private readonly Movie\Service\Select $movieSelectService,
        private readonly Movie\Genre\Service\Select $movieGenreSelectService,
        private readonly Environment $twig
    ) {
    }

    public function renderPage(Request $request) : Response
    {
        $movieId = (int)$request->getRouteParameters()['id'];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/movie.html.twig', [
                'movie' => $this->movieSelectService->findById($movieId),
                'movieGenres' => $this->movieGenreSelectService->findByMovieId($movieId),
                'watchDates' => $this->movieHistorySelectService->fetchHistoryByMovieId($movieId),
            ]),
        );
    }
}
