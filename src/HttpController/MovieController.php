<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie;
use Movary\Application\User\Service\Authentication;
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
        private readonly Authentication $authenticationService
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
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $movieId = (int)$request->getRouteParameters()['id'];

        $postParameters = $request->getPostParameters();

        $personalRating = null;
        if (empty($postParameters['rating']) === false && $postParameters['rating'] !== 0) {
            $personalRating = PersonalRating::create((int)$postParameters['rating']);
        }

        $this->movieApi->updatePersonalRating($movieId, $personalRating);

        return Response::create(
            StatusCode::createNoContent(),
        );
    }
}
