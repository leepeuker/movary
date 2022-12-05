<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\History\Service\Select;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class MostWatchedActorsController
{
    private const DEFAULT_LIMIT = 24;

    public function __construct(
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly Environment $twig,
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
        $limit = self::DEFAULT_LIMIT;

        $mostWatchedActors = $this->movieHistoryApi->fetchMostWatchedActors($userId, (int)$page, $limit, null, $searchTerm);
        $historyCount = $this->movieHistoryApi->fetchMostWatchedActorsCount($userId, $searchTerm);

        $maxPage = (int)ceil($historyCount / $limit);

        $paginationElements = [
            'previous' => $page > 1 ? $page - 1 : null,
            'next' => $page < $maxPage ? $page + 1 : null,
            'currentPage' => $page,
            'maxPage' => $maxPage,
        ];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/most-watched-actors.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor(),
                'mostWatchedActors' => $mostWatchedActors,
                'paginationElements' => $paginationElements,
                'searchTerm' => $searchTerm,
            ]),
        );
    }
}
