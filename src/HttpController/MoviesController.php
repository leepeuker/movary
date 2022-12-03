<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie;
use Movary\Application\User\Service\UserPageAuthorizationChecker;
use Movary\HttpController\Mapper\MoviesRequestMapper;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class MoviesController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Movie\MovieApi $movieApi,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private readonly MoviesRequestMapper $moviesRequestMapper
    ) {
    }

    public function renderPage(Request $request) : Response
    {
        $requestData = $this->moviesRequestMapper->mapRenderPageRequest($request);

        $userId = $requestData->getUserId();
        if ($userId === null) {
            return Response::createNotFound();
        }

        $uniqueMovies = $this->movieApi->fetchUniqueMoviesPaginated(
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
        $historyCount = $this->movieApi->fetchUniqueMoviesCount(
            $userId,
            $requestData->getSearchTerm(),
            $requestData->getReleaseYear(),
            $requestData->getLanguage(),
            $requestData->getGenre(),
        );

        $maxPage = (int)ceil($historyCount / $requestData->getLimit());

        $paginationElements = [
            'previous' => $requestData->getPage() > 1 ? $requestData->getPage() - 1 : null,
            'next' => $requestData->getPage() < $maxPage ? $requestData->getPage() + 1 : null,
            'currentPage' => $requestData->getPage(),
            'maxPage' => $maxPage,
        ];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/movies.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor(),
                'movies' => $uniqueMovies,
                'paginationElements' => $paginationElements,
                'searchTerm' => $requestData->getSearchTerm(),
                'perPage' => $requestData->getLimit(),
                'sortBy' => $requestData->getSortBy(),
                'sortOrder' => $requestData->getSortOrder(),
                'releaseYear' => (string)$requestData->getReleaseYear(),
                'language' => (string)$requestData->getLanguage(),
                'genre' => (string)$requestData->getGenre(),
                'uniqueReleaseYears' => $this->movieApi->fetchUniqueMovieReleaseYears($userId),
                'uniqueLanguages' => $this->movieApi->fetchUniqueMovieLanguages($userId),
                'uniqueGenres' => $this->movieApi->fetchUniqueMovieGenres($userId),
            ]),
        );
    }
}
