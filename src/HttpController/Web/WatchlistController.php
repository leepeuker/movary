<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\HttpController\Web\Mapper\WatchlistRequestMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use RuntimeException;
use Twig\Environment;

class WatchlistController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly MovieWatchlistApi $movieWatchlistApi,
        private readonly MovieApi $movieApi,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
        private readonly Authentication $authenticationService,
        private readonly SyncMovie $tmdbMovieSyncService,
        private readonly WatchlistRequestMapper $watchlistRequestMapper,
    ) {
    }

    public function addMovieToWatchlist(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $requestData = Json::decode($request->getBody());

        if (isset($requestData['tmdbId']) === false) {
            throw new RuntimeException('Missing parameters');
        }

        $tmdbId = (int)$requestData['tmdbId'];

        $movie = $this->movieApi->findByTmdbId($tmdbId);

        if ($movie === null) {
            $movie = $this->tmdbMovieSyncService->syncMovie($tmdbId);
        }

        $this->movieWatchlistApi->addMovieToWatchlist($userId, $movie->getId());

        return Response::create(StatusCode::createOk());
    }

    public function renderWatchlist(Request $request) : Response
    {
        $userId = $this->userPageAuthorizationChecker->findUserIdIfCurrentVisitorIsAllowedToSeeUser($request);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $requestData = $this->watchlistRequestMapper->mapRenderPageRequest($request);

        $watchlistPaginated = $this->movieWatchlistApi->fetchWatchlistPaginated(
            $userId,
            $requestData->getLimit(),
            $requestData->getPage(),
            $requestData->getSearchTerm(),
            $requestData->getSortBy(),
            $requestData->getSortOrder(),
            $requestData->getReleaseYear(),
            $requestData->getLanguage(),
            $requestData->getGenre(),
        );
        $watchlistCount = $this->movieWatchlistApi->fetchWatchlistCount(
            $userId,
            $requestData->getSearchTerm(),
            $requestData->getReleaseYear(),
            $requestData->getLanguage(),
            $requestData->getGenre(),
        );

        $paginationElements = $this->paginationElementsCalculator->createPaginationElements($watchlistCount, $requestData->getLimit(), $requestData->getPage());

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/watchlist.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor(),
                'watchlistEntries' => $watchlistPaginated,
                'paginationElements' => $paginationElements,
                'searchTerm' => $requestData->getSearchTerm(),
                'perPage' => $requestData->getLimit(),
                'sortBy' => $requestData->getSortBy(),
                'sortOrder' => (string)$requestData->getSortOrder(),
                'releaseYear' => (string)$requestData->getReleaseYear(),
                'language' => (string)$requestData->getLanguage(),
                'genre' => (string)$requestData->getGenre(),
                'uniqueReleaseYears' => $this->movieWatchlistApi->fetchUniqueMovieReleaseYears($userId),
                'uniqueLanguages' => $this->movieWatchlistApi->fetchUniqueMovieLanguages($userId),
                'uniqueGenres' => $this->movieWatchlistApi->fetchUniqueMovieGenres($userId),
            ]),
        );
    }
}
