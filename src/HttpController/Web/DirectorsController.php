<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\HttpController\Web\Mapper\PersonsRequestMapper;
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
        private readonly Authentication $authenticationService,
    ) {
    }

    public function renderPage(Request $request) : Response
    {
        $requestData = $this->requestMapper->mapRenderPageRequest($request);

        $currentUserId = null;
        if ($this->authenticationService->isUserAuthenticatedWithCookie() === true) {
            $currentUserId = $this->authenticationService->getCurrentUserId();
        }

        $personFilterUserId = $requestData->getSortBy() !== 'name' ? $currentUserId : null;

        $directors = $this->movieHistoryApi->fetchDirectors(
            $requestData->getUserId(),
            $requestData->getLimit(),
            $requestData->getPage(),
            $requestData->getSearchTerm(),
            $requestData->getSortBy(),
            $requestData->getSortOrder(),
            $requestData->getGender(),
            personFilterUserId: $personFilterUserId,
        );

        $directorsCount = $this->movieHistoryApi->fetchDirectorsCount(
            $requestData->getUserId(),
            $requestData->getSearchTerm(),
            $requestData->getGender(),
            personFilterUserId: $personFilterUserId,
        );
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
                'sortOrder' => (string)$requestData->getSortOrder(),
                'filterGender' => (string)$requestData->getGender(),
                'uniqueGenders' => $this->movieHistoryApi->fetchUniqueDirectorsGenders($requestData->getUserId())
            ]),
        );
    }
}
