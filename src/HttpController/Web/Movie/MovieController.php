<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Movie;

use Movary\Domain\Country\CountryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Domain\User\UserApi;
use Movary\Service\Imdb\ImdbMovieRatingSync;
use Movary\Service\ServerSettings;
use Movary\Service\SlugifyService;
use Movary\Service\Tmdb\SyncMovie;
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
        private readonly SlugifyService $slugify,
        private readonly CountryApi $countryApi,
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly ServerSettings $serverSettings,
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
        $userName = (string)$request->getRouteParameters()['username'];
        $userId = $this->userApi->fetchUserByName($userName)->getId();

        $currentUser = null;
        if ($this->authenticationService->isUserAuthenticatedWithCookie() === true) {
            $currentUser = $this->authenticationService->getCurrentUser();
        }

        $movieId = (int)$request->getRouteParameters()['id'];

        $movie = $this->movieApi->findByIdFormatted($movieId);

        if ($movie === null) {
            return Response::createNotFound();
        }

        $ignorablenameslug = (string)$request->getRouteParameters()['ignorablenameslug'];
        $movie_title_slug = $this->slugify->slugify($movie['title']);
        // redirect! if no slug or if slug is wrong
        if ($ignorablenameslug == "" || $ignorablenameslug != $movie_title_slug) {
            return Response::createMovedPermanently(
                $this->serverSettings->getApplicationUrl()
                    . "/users/"
                    . $userName
                    . "/movies/"
                    . $movieId
                    . "-"
                    . $movie_title_slug
            );
        }

        $movie['personalRating'] = $this->movieApi->findUserRating($movieId, $userId)?->asInt();

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/movie.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllHavingWatchedMovieVisibleUsernamesForCurrentVisitor($movieId),
                'movie' => $movie,
                'movieProductionCountries' => $this->movieApi->findProductionCountriesByMovieId($movieId),
                'movieGenres' => $this->movieApi->findGenresByMovieId($movieId),
                'castMembers' => $this->movieApi->findCastByMovieId($movieId),
                'directors' => $this->movieApi->findDirectorsByMovieId($movieId),
                'totalPlays' => $this->movieApi->fetchHistoryMovieTotalPlays($movieId, $userId),
                'watchDates' => $this->movieApi->fetchHistoryByMovieId($movieId, $userId),
                'isOnWatchlist' => $this->movieWatchlistApi->hasMovieInWatchlist($userId, $movieId),
                'countries' => $this->countryApi->getIso31661ToNameMap(),
                'displayCharacterNames' => $currentUser?->getDisplayCharacterNames() ?? true,
                'canonicalExtra' => '-' . $this->slugify->slugify($movie['title']),
            ]),
        );
    }
}
