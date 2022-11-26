<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie;
use Movary\Application\User\Service\UserPageAuthorizationChecker;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class MoviesController
{
    private const DEFAULT_LIMIT = 24;
    private const DEFAULT_SORT_BY = 'title';
    private const DEFAULT_SORT_ORDER = 'ASC';

    public function __construct(
        private readonly Environment $twig,
        private readonly Movie\Api $movieApi,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
    ) {
    }

    public function renderPage(Request $request) : Response
    {
        $userId = $this->userPageAuthorizationChecker->findUserIdIfCurrentVisitorIsAllowedToSeeUser((string)$request->getRouteParameters()['username']);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $searchTerm = $request->getGetParameters()['s'] ?? null;
        $page = $request->getGetParameters()['p'] ?? 1;
        $limit = $request->getGetParameters()['pp'] ?? self::DEFAULT_LIMIT;
        $sortBy = $request->getGetParameters()['sb'] ?? self::DEFAULT_SORT_BY;
        $sortOrder = $request->getGetParameters()['so'] ?? self::DEFAULT_SORT_ORDER;
        $releaseYear = $request->getGetParameters()['ry'] ?? null;
        $releaseYear = $releaseYear !== null ? (int)$releaseYear : $releaseYear;

        $uniqueMovies = $this->movieApi->fetchUniqueMoviesPaginated($userId, (int)$limit, (int)$page, $searchTerm, $sortBy, $sortOrder, $releaseYear);
        $historyCount = $this->movieApi->fetchUniqueMoviesCount($userId, $searchTerm);

        $maxPage = (int)ceil($historyCount / $limit);

        $paginationElements = [
            'previous' => $page > 1 ? $page - 1 : null,
            'next' => $page < $maxPage ? $page + 1 : null,
            'currentPage' => $page,
            'maxPage' => $maxPage,
        ];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/movies.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor(),
                'movies' => $uniqueMovies,
                'paginationElements' => $paginationElements,
                'searchTerm' => $searchTerm,
                'perPage' => $limit,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
                'releaseYear' => $releaseYear,
                'uniqueReleaseYears' => $this->movieApi->fetchUniqueMovieReleaseYears($userId),
            ]),
        );
    }
}
