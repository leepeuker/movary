<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Api\Tmdb\Cache\TmdbIsoCountryCache;
use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\RequestMapper\SearchRequestMapper;
use Movary\HttpController\Api\ResponseMapper\MovieSearchResponseMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

readonly class MovieController
{
    public function __construct(
        private Authentication $authenticationService,
        private TmdbApi $tmdbApi,
        private TmdbIsoCountryCache $tmdbIsoCountryCache,
        private PaginationElementsCalculator $paginationElementsCalculator,
        private SearchRequestMapper $searchRequestMapper,
        private MovieSearchResponseMapper $historyResponseMapper,
        private MovieApi $movieApi,
        private MovieWatchlistApi $movieWatchlistApi,
        private UserApi $userApi
    ) {
    }

    public function search(Request $request) : Response
    {
        $requestData = $this->searchRequestMapper->mapRequest($request);

        $tmdbResponse = $this->tmdbApi->searchMovie(
            $requestData->getSearchTerm(),
            $requestData->getYear(),
            $requestData->getPage(),
        );

        $paginationElements = $this->paginationElementsCalculator->createPaginationElements(
            $tmdbResponse['total_results'],
            (int)floor($tmdbResponse['total_results'] / $tmdbResponse['total_pages']),
            $requestData->getPage(),
        );

        return Response::createJson(
            Json::encode([
                'results' => $this->historyResponseMapper->mapMovieSearchResults($tmdbResponse),
                'currentPage' => $paginationElements->getCurrentPage(),
                'maxPage' => $paginationElements->getMaxPage(),
            ]),
        );
    }

    public function getMovie(Request $request) : Response
    {
        $requestedMovieId = (int)$request->getRouteParameters()['id'];
        $movie = $this->movieApi->findByIdFormatted($requestedMovieId);
        if($movie === null) {
            return Response::createNotFound();
        }
        $userId = $this->authenticationService->getUserIdByApiToken($request);
        if($userId === null) {
            $movieTotalPlays = null;
            $movieWatchDates = null;
            $isOnWatchlist = null;
            $displayCharacterNames = true;
        } else {
            $movieTotalPlays = $this->movieApi->fetchHistoryMovieTotalPlays($requestedMovieId, $userId);
            $movieWatchDates = $this->movieApi->fetchHistoryByMovieId($requestedMovieId, $userId);
            $isOnWatchlist = $this->movieWatchlistApi->hasMovieInWatchlist($userId, $requestedMovieId);
            $displayCharacterNames = $this->userApi->findUserById($userId)?->getDisplayCharacterNames() ?? true;
        }
        return Response::createJson(
            Json::encode([
                'movie' => $movie,
                'movieGenres' => $this->movieApi->findGenresByMovieId($requestedMovieId),
                'castMembers' => $this->movieApi->findCastByMovieId($requestedMovieId),
                'directors' => $this->movieApi->findDirectorsByMovieId($requestedMovieId),
                'totalPlays' => $movieTotalPlays,
                'watchDates' => $movieWatchDates,
                'isOnWatchlist' => $isOnWatchlist,
                'countries' => $this->tmdbIsoCountryCache->fetchAll(),
                'displayCharacterNames' => $displayCharacterNames
            ])
        );
    }
}
