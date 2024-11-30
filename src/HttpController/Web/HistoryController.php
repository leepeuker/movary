<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Domain\User\UserApi;
use Movary\Service\PaginationElementsCalculator;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Util\Json;
use Movary\ValueObject\Date;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\PersonalRating;
use RuntimeException;
use Twig\Environment;

class HistoryController
{
    private const int DEFAULT_LIMIT = 24;

    public function __construct(
        private readonly Environment $twig,
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly MovieApi $movieApi,
        private readonly UserApi $userApi,
        private readonly SyncMovie $tmdbMovieSyncService,
        private readonly Authentication $authenticationService,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
    ) {
    }

    public function createHistoryEntry(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        if ($this->userApi->fetchUser($userId)->getName() !== $request->getRouteParameters()['username']) {
            return Response::createForbidden();
        }

        $requestBody = Json::decode($request->getBody());

        $movieId = (int)$request->getRouteParameters()['id'];

        $dateFormat = $requestBody['dateFormat'];
        $newWatchDate = empty($requestBody['newWatchDate']) === false ? Date::createFromStringAndFormat($requestBody['newWatchDate'], $dateFormat) : null;
        $originalWatchDate = empty($requestBody['originalWatchDate']) === false ? Date::createFromStringAndFormat($requestBody['originalWatchDate'], $dateFormat) : null;

        $plays = empty($requestBody['plays']) === true ? 1 : (int)$requestBody['plays'];
        $comment = empty($requestBody['comment']) === true ? null : (string)$requestBody['comment'];
        $position = empty($requestBody['position']) === true ? 1 : (int)$requestBody['position'];
        $locationId = empty($requestBody['locationId']) === true ? null : (int)$requestBody['locationId'];

        $this->movieApi->updateHistoryComment($movieId, $userId, $newWatchDate, $comment);
        $this->movieApi->updateHistoryLocation($movieId, $userId, $newWatchDate, $locationId);

        if ($originalWatchDate == $newWatchDate) {
            $this->movieApi->replaceHistoryForMovieByDate($movieId, $userId, $newWatchDate, $plays, $position);

            return Response::create(StatusCode::createNoContent());
        }

        $this->movieApi->addPlaysForMovieOnDate($movieId, $userId, $newWatchDate, $plays, $position);
        $this->movieApi->deleteHistoryByIdAndDate($movieId, $userId, $originalWatchDate);

        return Response::create(StatusCode::createNoContent());
    }

    public function deleteHistoryEntry(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        if ($this->userApi->fetchUser($userId)->getName() !== $request->getRouteParameters()['username']) {
            return Response::createForbidden();
        }

        $requestBody = Json::decode($request->getBody());

        $movieId = (int)$request->getRouteParameters()['id'];
        $date = empty($requestBody['date']) === false ? Date::createFromStringAndFormat($requestBody['date'], $requestBody['dateFormat']) : null;

        $this->movieApi->deleteHistoryByIdAndDate($movieId, $userId, $date);

        return Response::create(StatusCode::createNoContent());
    }

    public function logMovie(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $requestData = Json::decode($request->getBody());

        if (isset($requestData['watchDate'], $requestData['tmdbId'], $requestData['personalRating']) === false) {
            throw new RuntimeException('Missing parameters');
        }

        $watchDate = empty($requestData['watchDate']) === false ? Date::createFromStringAndFormat($requestData['watchDate'], $requestData['dateFormat']) : null;
        $tmdbId = (int)$requestData['tmdbId'];
        $personalRating = $requestData['personalRating'] === 0 ? null : PersonalRating::create((int)$requestData['personalRating']);
        $comment = empty($requestData['comment']) === true ? null : (string)$requestData['comment'];
        $locationId = empty($requestData['locationId']) === true ? null : (int)$requestData['locationId'];

        $movie = $this->movieApi->findByTmdbId($tmdbId);

        if ($movie === null) {
            $movie = $this->tmdbMovieSyncService->syncMovie($tmdbId);
        }

        $this->movieApi->updateUserRating($movie->getId(), $userId, $personalRating);
        $this->movieApi->addPlaysForMovieOnDate($movie->getId(), $userId, $watchDate);
        $this->movieApi->updateHistoryComment($movie->getId(), $userId, $watchDate, $comment);
        $this->movieApi->updateHistoryLocation($movie->getId(), $userId, $watchDate, $locationId);

        return Response::create(StatusCode::createOk());
    }

    public function renderHistory(Request $request) : Response
    {
        $userId = $this->userApi->fetchUserByName((string)$request->getRouteParameters()['username'])->getId();
        $searchTerm = $request->getGetParameters()['s'] ?? null;
        $page = $request->getGetParameters()['p'] ?? 1;
        $limit = self::DEFAULT_LIMIT;

        $historyPaginated = $this->movieHistoryApi->fetchHistoryPaginated($userId, $limit, (int)$page, $searchTerm);
        $historyCount = $this->movieHistoryApi->fetchHistoryCount($userId, $searchTerm);

        $paginationElements = $this->paginationElementsCalculator->createPaginationElements($historyCount, $limit, (int)$page);

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/history.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor(),
                'historyEntries' => $historyPaginated,
                'paginationElements' => $paginationElements,
                'searchTerm' => $searchTerm,
            ]),
        );
    }
}
