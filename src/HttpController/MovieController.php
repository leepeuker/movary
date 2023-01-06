<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Domain\User\UserApi;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\PersonalRating;
use Twig\Environment;

class MovieController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly MovieApi $movieApi,
        private readonly UserApi $userApi,
        private readonly Authentication $authenticationService,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
    ) {
    }

    public function fetchMovieRatingByTmdbdId(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();
        $tmdbId = $request->getGetParameters()['tmdbId'] ?? null;

        $userRating = null;
        $movie = $this->movieApi->findByTmdbId((int)$tmdbId);

        if ($movie !== null) {
            $userRating = $this->movieApi->findUserRating($movie->getId(), $userId);
        }

        return Response::createJson(
            Json::encode(['personalRating' => $userRating?->asInt()]),
        );
    }

    public function renderPage(Request $request) : Response
    {
        $userId = $this->userPageAuthorizationChecker->findUserIdIfCurrentVisitorIsAllowedToSeeUser((string)$request->getRouteParameters()['username']);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $movieId = (int)$request->getRouteParameters()['id'];

        $movie = $this->movieApi->findById($movieId);
        $movie['personalRating'] = $this->movieApi->findUserRating($movieId, $userId)?->asInt();

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/movie.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllHavingWatchedMovieVisibleUsernamesForCurrentVisitor($movieId),
                'movie' => $movie,
                'movieGenres' => $this->movieApi->findGenresByMovieId($movieId),
                'castMembers' => $this->movieApi->findCastByMovieId($movieId),
                'directors' => $this->movieApi->findDirectorsByMovieId($movieId),
                'totalPlays' => $this->movieApi->fetchHistoryMovieTotalPlays($movieId, $userId),
                'watchDates' => $this->movieApi->fetchHistoryByMovieId($movieId, $userId),
            ]),
        );
    }

    public function updateRating(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createForbidden();
        }

        $userId = $this->authenticationService->getCurrentUserId();

        if ($this->userApi->fetchUser($userId)->getName() !== $request->getRouteParameters()['username']) {
            return Response::createForbidden();
        }

        $movieId = (int)$request->getRouteParameters()['id'];

        $postParameters = $request->getPostParameters();

        $personalRating = null;
        if (empty($postParameters['rating']) === false && $postParameters['rating'] !== 0) {
            $personalRating = PersonalRating::create((int)$postParameters['rating']);
        }

        $this->movieApi->updateUserRating($movieId, $this->authenticationService->getCurrentUserId(), $personalRating);

        return Response::create(StatusCode::createNoContent());
    }
}
