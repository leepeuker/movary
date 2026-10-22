<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Movie;

use Movary\Domain\Country\CountryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Review\MovieReviewApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\CurrentWebUser;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Domain\User\UserApi;
use Movary\Service\Imdb\ImdbMovieRatingSync;
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
        private readonly CurrentWebUser $currentWebUser,
        private readonly UserApi $userApi,
        private readonly MovieReviewApi $movieReviewApi,
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
        $userName = (string)$request->getRouteParameters()['username'];
        $userId = $this->userApi->fetchUserByName($userName)->getId();

        $currentUser = $this->currentWebUser->findUser();

        $movieId = (int)$request->getRouteParameters()['id'];

        $movie = $this->movieApi->findByIdFormatted($movieId);

        if ($movie === null) {
            return Response::createNotFound();
        }

        $movie['personalRating'] = $this->movieApi->findUserRating($movieId, $userId)?->asInt();

        $visibleReviews = $this->movieReviewApi->fetchVisibleForMovie($movieId, $currentUser?->getId());
        $otherReviews = array_values(array_filter(
            $visibleReviews,
            static fn(array $review) : bool => $review['userId'] !== $userId,
        ));

        $users = $this->mergeVisibleReviewers(
            $this->userPageAuthorizationChecker->fetchAllHavingWatchedMovieVisibleUsernamesForCurrentVisitor(
                $movieId,
            ),
            $visibleReviews,
        );

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/movie.html.twig', [
                'users' => $users,
                'movie' => $movie,
                'selectedUserReview' => $this->movieReviewApi->find($movieId, $userId),
                'otherReviews' => $otherReviews,
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
                'displayTmdbRating' => $currentUser?->getDisplayTmdbRating() ?? true,
                'displayImdbRating' => $currentUser?->getDisplayImdbRating() ?? true,
            ]),
        );
    }

    private function mergeVisibleReviewers(array $users, array $visibleReviews) : array
    {
        $usernames = array_column($users, 'name');
        foreach ($visibleReviews as $visibleReview) {
            if (in_array($visibleReview['username'], $usernames, true) === true) {
                continue;
            }

            $users[] = ['name' => $visibleReview['username']];
            $usernames[] = $visibleReview['username'];
        }
        usort($users, static fn(array $left, array $right) : int => $left['name'] <=> $right['name']);

        return $users;
    }
}
