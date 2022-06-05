<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Api\Tmdb;
use Movary\Application\Movie;
use Movary\Application\Movie\History\Service\Select;
use Movary\Application\Service\Tmdb\SyncMovie;
use Movary\Application\SessionService;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class HistoryController
{
    private const DEFAULT_LIMIT = 24;

    public function __construct(
        private readonly Environment $twig,
        private readonly Select $movieHistorySelectService,
        private readonly Tmdb\Api $tmdbApi,
        private readonly Movie\Api $movieApi,
        private readonly SyncMovie $tmdbMovieSyncService,
        private readonly SessionService $sessionService
    ) {
    }

    public function logMovie(Request $request) : Response
    {
        if ($this->sessionService->isCurrentUserLoggedIn() === false) {
            return Response::createFoundRedirect('/');
        }

        $postParameters = $request->getPostParameters();

        $watchDate = Date::createFromDateTime(DateTime::createFromString($postParameters['watchDate']));
        $tmdbId = (int)$postParameters['tmdbId'];
        $rating10 = (int)$postParameters['rating10'];
        $rating5 = (int)$postParameters['rating5'];

        $movie = $this->movieApi->findByTmdbId($tmdbId);

        if ($movie === null) {
            $movie = $this->tmdbMovieSyncService->syncMovie($tmdbId);
        }

        $this->movieApi->updateRating5($movie->getId(), $rating5);
        $this->movieApi->updateRating10($movie->getId(), $rating10);
        $this->movieApi->replaceHistoryForMovieByDate($movie->getId(), $watchDate, 1);

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }

    public function renderHistory(Request $request) : Response
    {
        $searchTerm = $request->getGetParameters()['s'] ?? null;
        $page = $request->getGetParameters()['p'] ?? 1;
        $limit = self::DEFAULT_LIMIT;

        $historyPaginated = $this->movieHistorySelectService->fetchHistoryPaginated($limit, (int)$page, $searchTerm);
        $historyCount = $this->movieHistorySelectService->fetchHistoryCount($searchTerm);

        $maxPage = (int)ceil($historyCount / $limit);

        $paginationElements = [
            'previous' => $page > 1 ? $page - 1 : null,
            'next' => $page < $maxPage ? $page + 1 : null,
            'currentPage' => $page,
            'maxPage' => $maxPage,
        ];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/history.html.twig', [
                'historyEntries' => $historyPaginated,
                'paginationElements' => $paginationElements,
                'searchTerm' => $searchTerm,
            ]),
        );
    }

    public function renderLogMoviePage(Request $request) : Response
    {
        if ($this->sessionService->isCurrentUserLoggedIn() === false) {
            return Response::createFoundRedirect('/');
        }

        $searchTerm = $request->getGetParameters()['s'] ?? null;

        $movies = [];
        if ($searchTerm !== null) {
            $movies = $this->tmdbApi->searchMovie($searchTerm);
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/logMovie.html.twig', [
                'movies' => $movies,
                'searchTerm' => $searchTerm,
            ]),
        );
    }
}
