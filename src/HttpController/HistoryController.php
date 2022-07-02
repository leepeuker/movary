<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Api\Tmdb;
use Movary\Application\Movie;
use Movary\Application\Movie\History\Service\Select;
use Movary\Application\Service\Tmdb\SyncMovie;
use Movary\Application\User\Service\Authentication;
use Movary\Util\Json;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\PersonalRating;
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
        private readonly Authentication $authenticationService
    ) {
    }

    public function deleteHistoryEntry(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $requestBody = Json::decode($request->getBody());

        $movieId = (int)$request->getRouteParameters()['id'];
        $date = Date::createFromString($requestBody['date']);
        $count = $requestBody['count'] ?? 1;

        $this->movieApi->deleteHistoryByIdAndDate($movieId, $date, $count);

        return Response::create(StatusCode::createOk());
    }

    public function logMovie(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $requestData = Json::decode($request->getBody());

        if (isset($requestData['watchDate'], $requestData['tmdbId'], $requestData['personalRating']) === false) {
            throw new \RuntimeException('Missing parameters');
        }

        $watchDate = Date::createFromDateTime(DateTime::createFromString($requestData['watchDate']));
        $tmdbId = (int)$requestData['tmdbId'];
        $personalRating = $requestData['personalRating'] === 0 ? null : PersonalRating::create((int)$requestData['personalRating']);

        $movie = $this->movieApi->findByTmdbId($tmdbId);

        if ($movie === null) {
            $movie = $this->tmdbMovieSyncService->syncMovie($tmdbId);
        }

        $this->movieApi->updatePersonalRating($movie->getId(), $personalRating);
        $this->movieApi->increaseHistoryPlaysForMovieOnDate($movie->getId(), $watchDate);

        return Response::create(StatusCode::createOk());
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
        if ($this->authenticationService->isUserAuthenticated() === false) {
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
