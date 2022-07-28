<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie;
use Movary\Application\Person;
use Movary\Application\User\Service\UserPageAuthorizationChecker;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class PersonController
{
    public function __construct(
        private readonly Person\Api $personApi,
        private readonly Movie\Api $movieApi,
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

        $personId = (int)$request->getRouteParameters()['id'];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/person.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllHavingWatchedMovieWithPersonVisibleUsernamesForCurrentVisitor($personId),
                'person' => $this->personApi->findById($personId),
                'moviesAsActor' => $this->movieApi->fetchWithActor($personId, $userId),
                'moviesAsDirector' => $this->movieApi->fetchWithDirector($personId, $userId),
            ]),
        );
    }
}
