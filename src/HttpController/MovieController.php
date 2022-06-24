<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie;
use Movary\Application\SessionService;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\PersonalRating;
use Twig\Environment;

class MovieController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Movie\Api $movieApi,
        private readonly SessionService $sessionService
    ) {
    }

    public function renderPage(Request $request) : Response
    {
        $movieId = (int)$request->getRouteParameters()['id'];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/movie.html.twig', [
                'movie' => $this->movieApi->findById($movieId),
                'movieGenres' => $this->movieApi->findGenresByMovieId($movieId),
                'castMembers' => $this->movieApi->findCastByMovieId($movieId),
                'directors' => $this->movieApi->findDirectorsByMovieId($movieId),
                'watchDates' => $this->movieApi->fetchHistoryByMovieId($movieId),
            ]),
        );
    }

    public function updateRating(Request $request) : Response
    {
        if ($this->sessionService->isCurrentUserLoggedIn() === false) {
            return Response::createFoundRedirect('/');
        }

        $movieId = (int)$request->getRouteParameters()['id'];

        $postParameters = $request->getPostParameters();
        $personalRating = empty($postParameters['rating']) === true ? null : (int)$postParameters['rating'];

        if ($personalRating === 0 || $personalRating === null) {
            $personalRating = null;
        } else {
            $personalRating = PersonalRating::create($personalRating);
        }

        if (isset($postParameters['rating']) === true) {
            $this->movieApi->updatePersonalRating($movieId, $personalRating);
        }

        if (empty($_SERVER['HTTP_REFERER']) === true) {
            return Response::create(
                StatusCode::createNoContent(),
            );
        }

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }
}
