<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\HttpController\Web\Mapper\MoviesRequestMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class MoviesController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly MovieApi $movieApi,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private readonly MoviesRequestMapper $moviesRequestMapper,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
    ) {
    }

    public function renderPage(Request $request) : Response
    {
        $requestData = $this->moviesRequestMapper->mapRenderPageRequest($request);

        $uniqueMovies = $this->movieApi->fetchUniqueWatchedMoviesPaginated(
            $requestData->getUserId(),
            $requestData->getLimit(),
            $requestData->getPage(),
            $requestData->getSearchTerm(),
            $requestData->getSortBy(),
            $requestData->getSortOrder(),
            $requestData->getReleaseYear(),
            $requestData->getLanguage(),
            $requestData->getGenre(),
            $requestData->hasUserRating(),
            $requestData->getUserRatingMin(),
            $requestData->getUserRatingMax(),
            $requestData->getLocationId(),
        );

        $watchedMoviesCount = $this->movieApi->fetchUniqueWatchedMoviesCount(
            $requestData->getUserId(),
            $requestData->getSearchTerm(),
            $requestData->getReleaseYear(),
            $requestData->getLanguage(),
            $requestData->getGenre(),
            $requestData->hasUserRating(),
            $requestData->getUserRatingMin(),
            $requestData->getUserRatingMax(),
            $requestData->getLocationId(),
        );
        $paginationElements = $this->paginationElementsCalculator->createPaginationElements($watchedMoviesCount, $requestData->getLimit(), $requestData->getPage());

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/movies.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor(),
                'movies' => $uniqueMovies,
                'paginationElements' => $paginationElements,
                'searchTerm' => $requestData->getSearchTerm(),
                'perPage' => $requestData->getLimit(),
                'sortBy' => $requestData->getSortBy(),
                'sortOrder' => (string)$requestData->getSortOrder(),
                'releaseYear' => (string)$requestData->getReleaseYear(),
                'language' => (string)$requestData->getLanguage(),
                'genre' => (string)$requestData->getGenre(),
                'locationId' => $requestData->getLocationId(),
                'uniqueReleaseYears' => $this->movieApi->fetchUniqueMovieReleaseYears($requestData->getUserId()),
                'uniqueLanguages' => $this->movieApi->fetchUniqueMovieLanguages($requestData->getUserId()),
                'uniqueGenres' => $this->movieApi->fetchUniqueMovieGenres($requestData->getUserId()),
                'uniqueLocations' => $this->movieApi->fetchUniqueLocations($requestData->getUserId()),
                'hasUserRating' => $requestData->hasUserRating(),
                'minUserRating' => $requestData->getUserRatingMin(),
                'maxUserRating' => $requestData->getUserRatingMax(),
            ]),
        );
    }
}
