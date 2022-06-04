<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Api\Tmdb;
use Movary\Application\Movie;
use Movary\Application\Movie\History\Service\Select;
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
    ) {
    }

    public function addMovie(Request $request) : Response
    {
        $watchDate = $request->getPostParameters()['watchDate'];
        $tmdbId = $request->getPostParameters()['tmdbId'];

        $movie = $this->tmdbApi->getMovieDetails((int)$tmdbId);

        var_dump($movie);
        // $this->movieApi->create($movie->getTitle(), null, null, null, null, $tmdbId);
        exit;

        return Response::create(
            StatusCode::createSeeOther(),
            null,
            [Header::createLocation($_SERVER['HTTP_REFERER'])]
        );
    }

    public function renderAddMoviePage(Request $request) : Response
    {
        $searchTerm = $request->getGetParameters()['s'] ?? null;

        $movies = [];
        if ($searchTerm !== null) {
            $movies = $this->tmdbApi->searchMovie($searchTerm);
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/addMovie.html.twig', [
                'movies' => $movies,
            ]),
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
}
