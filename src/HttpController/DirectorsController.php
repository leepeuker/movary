<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\HttpController\Mapper\PersonsRequestMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class DirectorsController
{
    public function __construct(
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly Environment $twig,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private readonly PersonsRequestMapper $requestMapper,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
    ) {
    }

    public function renderPage(Request $request) : Response
    {
        $userId = $this->userPageAuthorizationChecker->findUserIdIfCurrentVisitorIsAllowedToSeeUser((string)$request->getRouteParameters()['username']);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $requestData = $this->requestMapper->mapRenderPageRequest($request);

        $directors = $this->movieHistoryApi->fetchDirectors(
            $userId,
            $requestData->getLimit(),
            $requestData->getPage(),
            $requestData->getSearchTerm(),
            $requestData->getSortBy(),
            $requestData->getSortOrder(),
            $requestData->getGender(),
        );

        $directorsCount = $this->movieHistoryApi->fetchDirectorsCount($userId, $requestData->getSearchTerm());
        $paginationElements = $this->paginationElementsCalculator->createPaginationElements($directorsCount, $requestData->getLimit(), $requestData->getPage());

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/directors.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllVisibleUsernamesForCurrentVisitor(),
                'mostWatchedDirectors' => $directors,
                'paginationElements' => $paginationElements,
                'searchTerm' => $requestData->getSearchTerm(),
                'perPage' => $requestData->getLimit(),
                'sortBy' => $requestData->getSortBy(),
                'sortOrder' => $requestData->getSortOrder(),
                'filterGender' => (string)$requestData->getGender(),
                'uniqueGenders' => $this->movieHistoryApi->fetchUniqueDirectorsGenders($userId)
            ]),
        );
    }
}
