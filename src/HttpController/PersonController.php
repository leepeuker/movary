<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Person;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Service\UrlGenerator;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class PersonController
{
    public function __construct(
        private readonly Person\PersonApi $personApi,
        private readonly MovieApi $movieApi,
        private readonly Environment $twig,
        private readonly UserPageAuthorizationChecker $userPageAuthorizationChecker,
        private readonly UrlGenerator $urlGenerator,
    ) {
    }

    public function renderPage(Request $request) : Response
    {
        $userId = $this->userPageAuthorizationChecker->findUserIdIfCurrentVisitorIsAllowedToSeeUser((string)$request->getRouteParameters()['username']);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $personId = (int)$request->getRouteParameters()['id'];

        $person = $this->personApi->findById($personId);
        if ($person === null) {
            return Response::createNotFound();
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/person.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllHavingWatchedMovieWithPersonVisibleUsernamesForCurrentVisitor($personId),
                'person' => [
                    'name' => $person->getName(),
                    'posterPath' => $this->urlGenerator->generateImageSrcUrlFromParameters($person->getTmdbPosterPath(), $person->getPosterPath()),
                    'knownForDepartment' => $person->getKnownForDepartment(),
                    'gender' => $person->getGender(),
                ],
                'moviesAsActor' => $this->movieApi->fetchWithActor($personId, $userId),
                'moviesAsDirector' => $this->movieApi->fetchWithDirector($personId, $userId),
            ]),
        );
    }
}
