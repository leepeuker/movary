<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Movie;

use Movary\Api\Tmdb\Cache\TmdbIsoCountryCache;
use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Service\Imdb\ImdbMovieRatingSync;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class MovieController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly MovieApi $movieApi,
        private readonly MovieWatchlistApi $movieWatchlistApi,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private readonly SyncMovie $tmdbMovieSync,
        private readonly ImdbMovieRatingSync $imdbMovieRatingSync,
        private readonly TmdbIsoCountryCache $tmdbIsoCountryCache,
        private readonly TmdbApi $tmdbApi,
        private readonly Authentication $authenticationService,
    ) {
    }

    public function refreshImdbRating(Request $request) : Response
    {
        $movieId = (int)$request->getRouteParameters()['id'];
        $movie = $this->movieApi->findByIdFormatted($movieId);

        if ($movie === null) {
            return Response::createNotFound();
        }

        $this->imdbMovieRatingSync->syncMovieRating($movieId);

        return Response::createOk();
    }

    public function refreshTmdbData(Request $request) : Response
    {
        $movieId = (int)$request->getRouteParameters()['id'];

        $movie = $this->movieApi->findByIdFormatted($movieId);
        if ($movie === null) {
            return Response::createNotFound();
        }

        $tmdbId = $movie['tmdbId'] ?? null;
        if ($tmdbId === null) {
            return Response::createOk();
        }

        $this->tmdbMovieSync->syncMovie($tmdbId);

        return Response::createOk();
    }

    public function renderPage(Request $request) : Response
    {
        $userId = $this->userPageAuthorizationChecker->findUserIdIfCurrentVisitorIsAllowedToSeeUser((string)$request->getRouteParameters()['username']);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $currentUser = null;
        if ($this->authenticationService->isUserAuthenticated() === true) {
            $currentUser = $this->authenticationService->getCurrentUser();
        }
        $canChangePoster = $currentUser->isAdmin();

        $movieId = (int)$request->getRouteParameters()['id'];

        $movie = $this->movieApi->findByIdFormatted($movieId);

        if ($movie === null) {
            return Response::createNotFound();
        }

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
                'isOnWatchlist' => $this->movieWatchlistApi->hasMovieInWatchlist($userId, $movieId),
                'countries' => $this->tmdbIsoCountryCache->fetchAll(),
                'displayCharacterNames' => $currentUser?->getDisplayCharacterNames() ?? true,
                'canChangePoster' => $canChangePoster,
            ]),
        );
    }

    public function getPosters(Request $request) : Response
    {
        $movieId = (int)$request->getRouteParameters()['id'];
        $movie = $this->movieApi->findById($movieId);
        if($movie === null) {
            return Response::createNotFound();
        }
        $tmdbId = $movie->getTmdbId();
        if($tmdbId === null) {
            return Response::createBadRequest();
        }
        $images = $this->tmdbApi->getImages($tmdbId)['posters'];
        return Response::createJson(Json::encode($images));
    }

    public function updatePoster(Request $request) : Response
    {
        $movieId = (int)$request->getRouteParameters()['id'];
        $movie = $this->movieApi->findById($movieId);
        if($movie === null) {
            return Response::createNotFound();
        }
        $posterFilepath = $request->getBody();
        $oldPosterPath = $movie->getPosterPath();
        if($posterFilepath === $movie->getTmdbPosterPath()) {
            return Response::createBadRequest();
        }
        $this->movieApi->updatePosterPath($movieId, $posterFilepath, $oldPosterPath);
        return Response::createOk();
    }
}
