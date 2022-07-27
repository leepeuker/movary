<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie\History\Service\Select;
use Movary\Application\User\Api;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class MostWatchedDirectorsController
{
    private const DEFAULT_LIMIT = 24;

    public function __construct(
        private readonly Select $movieHistorySelectService,
        private readonly Environment $twig,
        private readonly Api $userApi,
    ) {
    }

    public function renderPage(Request $request) : Response
    {
        $userId = $this->userApi->findUserByName((string)$request->getRouteParameters()['username'])?->getId();
        if ($userId === null) {
            return Response::createNotFound();
        }

        $searchTerm = $request->getGetParameters()['s'] ?? null;
        $page = $request->getGetParameters()['p'] ?? 1;
        $limit = self::DEFAULT_LIMIT;

        $mostWatchedActors = $this->movieHistorySelectService->fetchMostWatchedDirectors($userId, (int)$page, $limit, $searchTerm);
        $historyCount = $this->movieHistorySelectService->fetchMostWatchedDirectorsCount($userId, $searchTerm);

        $maxPage = (int)ceil($historyCount / $limit);

        $paginationElements = [
            'previous' => $page > 1 ? $page - 1 : null,
            'next' => $page < $maxPage ? $page + 1 : null,
            'currentPage' => $page,
            'maxPage' => $maxPage,
        ];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/mostWatchedDirectors.html.twig', [
                'users' => $this->userApi->fetchAll(),
                'mostWatchedDirectors' => $mostWatchedActors,
                'paginationElements' => $paginationElements,
                'searchTerm' => $searchTerm,
            ]),
        );
    }
}
