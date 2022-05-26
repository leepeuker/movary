<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie\History\Service\Select;
use Movary\Util\Json;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class MostWatchedActorsController
{
    private const DEFAULT_LIMIT = 24;

    public function __construct(
        private readonly Select $movieHistorySelectService,
        private readonly Environment $twig
    ) {
    }

    public function fetchMostWatchedActors() : Response
    {
        return Response::create(
            StatusCode::createOk(),
            Json::encode($this->movieHistorySelectService->fetchMostWatchedActors()),
            [Header::createContentTypeJson()]
        );
    }

    public function renderPage(Request $request) : Response
    {
        $searchTerm = $request->getGetParameters()['s'] ?? null;
        $page = $request->getGetParameters()['p'] ?? 1;
        $limit = self::DEFAULT_LIMIT;

        $mostWatchedActors = $this->movieHistorySelectService->fetchMostWatchedActors((int)$page, $limit, null, $searchTerm);
        $historyCount = $this->movieHistorySelectService->fetchMostWatchedActorsCount($searchTerm);

        $maxPage = (int)ceil($historyCount / $limit);

        $paginationElements = [
            'previous' => $page > 1 ? $page - 1 : null,
            'next' => $page < $maxPage ? $page + 1 : null,
            'currentPage' => $page,
            'maxPage' => $maxPage,
        ];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/mostWatchedActors.html.twig', [
                'mostWatchedActors' => $mostWatchedActors,
                'paginationElements' => $paginationElements,
                'searchTerm' => $searchTerm,
            ]),
        );
    }
}
