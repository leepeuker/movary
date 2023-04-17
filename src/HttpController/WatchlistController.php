<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Service\PaginationElementsCalculator;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class WatchlistController
{
    private const DEFAULT_LIMIT = 24;

    public function __construct(
        private readonly Environment $twig,
        private readonly MovieWatchlistApi $movieWatchlistApi,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
    ) {
    }

    public function renderWatchlist(Request $request) : Response
    {
        $userId = $this->userPageAuthorizationChecker->findUserIdIfCurrentVisitorIsAllowedToSeeUser((string)$request->getRouteParameters()['username']);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $searchTerm = $request->getGetParameters()['s'] ?? null;
        $page = $request->getGetParameters()['p'] ?? 1;
        $limit = self::DEFAULT_LIMIT;

        $historyPaginated = $this->movieWatchlistApi->fetchWatchlistPaginated($userId, $limit, (int)$page, $searchTerm);
        $historyCount = $this->movieWatchlistApi->fetchWatchlistCount($userId, $searchTerm);

        $paginationElements = $this->paginationElementsCalculator->createPaginationElements($historyCount, $limit, (int)$page);

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/watchlist.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor(),
                'watchlistEntries' => $historyPaginated,
                'paginationElements' => $paginationElements,
                'searchTerm' => $searchTerm,
            ]),
        );
    }
}
