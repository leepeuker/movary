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
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\PersonalRating;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Twig\Environment;

class HistoryController
{
    private const DEFAULT_LIMIT = 24;

    public function __construct(
        private readonly Environment $twig,
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly MovieApi $movieApi,
        private readonly UserApi $userApi,
        private readonly SyncMovie $tmdbMovieSyncService,
        private readonly Authentication $authenticationService,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
        private readonly LoggerInterface $logger,
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

        $plays = (int)$requestBody['plays'];
        $comment = empty($requestBody['comment']) === true ? null : (string)$requestBody['comment'];

        if ($originalWatchDate == $newWatchDate) {
            $this->movieApi->replaceHistoryForMovieByDate($movieId, $userId, $newWatchDate, $plays, $comment);

            return Response::create(StatusCode::createNoContent());
        }

        $this->movieApi->addPlaysForMovieOnDate($movieId, $userId, $newWatchDate, $plays);
        $this->movieApi->deleteHistoryByIdAndDate($movieId, $userId, $originalWatchDate);

        if ($comment !== null) {
            $this->movieApi->updateHistoryComment($movieId, $userId, $newWatchDate, $comment);
        }

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
        $this->logger->debug('CREATE_MOVIE_LOG start - ' . DateTime::create()->format('Y-m-d H:i:s.u'));
        $userId = $this->authenticationService->getCurrentUserId();

        $requestData = Json::decode($request->getBody());

        if (isset($requestData['watchDate'], $requestData['tmdbId'], $requestData['personalRating']) === false) {
            throw new RuntimeException('Missing parameters');
        }

        $watchDate = empty($requestData['watchDate']) === false ? Date::createFromStringAndFormat($requestData['watchDate'], $requestData['dateFormat']) : null;
        $tmdbId = (int)$requestData['tmdbId'];
        $personalRating = $requestData['personalRating'] === 0 ? null : PersonalRating::create((int)$requestData['personalRating']);
        $comment = empty($requestData['comment']) === true ? null : (string)$requestData['comment'];

        $this->logger->debug('CREATE_MOVIE_LOG before local tmdb search - ' . DateTime::create()->format('Y-m-d H:i:s.u'));
        $movie = $this->movieApi->findByTmdbId($tmdbId);
        $this->logger->debug('CREATE_MOVIE_LOG after local tmdb search - ' . DateTime::create()->format('Y-m-d H:i:s.u'));

        if ($movie === null) {
            $movie = $this->tmdbMovieSyncService->syncMovie($tmdbId);
        }

        $this->movieApi->updateUserRating($movie->getId(), $userId, $personalRating);
        $this->movieApi->addPlaysForMovieOnDate($movie->getId(), $userId, $watchDate);
        $this->movieApi->updateHistoryComment($movie->getId(), $userId, $watchDate, $comment);

        $this->logger->debug('CREATE_MOVIE_LOG end - ' . DateTime::create()->format('Y-m-d H:i:s.u'));

        return Response::create(StatusCode::createOk());
    }

    public function renderHistory(Request $request) : Response
    {
        $userId = $this->userPageAuthorizationChecker->findUserIdIfCurrentVisitorIsAllowedToSeeUser((string)$request->getRouteParameters()['username']);
        if ($userId === null) {
            return Response::createNotFound();
        }

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
