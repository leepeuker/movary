<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\HttpController\Mapper\ActorsRequestMapper;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class ActorsController
{
    public function __construct(
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly Environment $twig,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private readonly ActorsRequestMapper $requestMapper,
    ) {
    }

    public function renderPage(Request $request) : Response
    {
        $userId = $this->userPageAuthorizationChecker->findUserIdIfCurrentVisitorIsAllowedToSeeUser((string)$request->getRouteParameters()['username']);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $requestData = $this->requestMapper->mapRenderPageRequest($request);

        $mostWatchedActors = $this->movieHistoryApi->fetchActors(
            $userId,
            $requestData->getLimit(),
            $requestData->getPage(),
            $requestData->getSearchTerm(),
            $requestData->getSortBy(),
            $requestData->getSortOrder(),
            $requestData->getGender(),
        );
        $historyCount = $this->movieHistoryApi->fetchMostWatchedActorsCount($userId, $requestData->getSearchTerm());

        $maxPage = (int)ceil($historyCount / $requestData->getLimit());

        $paginationElements = [
            'previous' => $requestData->getPage() > 1 ? $requestData->getPage() - 1 : null,
            'next' => $requestData->getPage() < $maxPage ? $requestData->getPage() + 1 : null,
            'currentPage' => $requestData->getPage(),
            'maxPage' => $maxPage,
        ];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/actors.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor(),
                'mostWatchedActors' => $mostWatchedActors,
                'paginationElements' => $paginationElements,
                'searchTerm' => $requestData->getSearchTerm(),
                'perPage' => $requestData->getLimit(),
                'sortBy' => $requestData->getSortBy(),
                'sortOrder' => $requestData->getSortOrder(),
                'filterGender' => (string)$requestData->getGender(),
                'uniqueGenders' => $this->movieHistoryApi->fetchUniqueActorGenders($userId)
            ]),
        );
    }
}
