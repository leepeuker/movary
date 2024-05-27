<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Api\Imdb;
use Movary\Api\Tmdb;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Person;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\UserPageAuthorizationChecker;
use Movary\Domain\User\UserApi;
use Movary\Service\Tmdb\SyncPerson;
use Movary\Service\UrlGenerator;
use Movary\ValueObject\Date;
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
        private readonly Imdb\ImdbUrlGenerator $imdbUrlGenerator,
        private readonly Tmdb\TmdbUrlGenerator $tmdbUrlGenerator,
        private readonly SyncPerson $tmdbPersonSync,
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
    ) {
    }

    public function hideInTopLists(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $personId = (int)$request->getRouteParameters()['id'];

        $person = $this->personApi->findById($personId);
        if ($person === null) {
            return Response::createNotFound();
        }

        $this->personApi->updateHideInTopLists($userId, $person->getId(), true);

        return Response::createOk();
    }

    public function refreshTmdbData(Request $request) : Response
    {
        $personId = (int)$request->getRouteParameters()['id'];

        $person = $this->personApi->findById($personId);
        if ($person === null) {
            return Response::createNotFound();
        }

        $this->tmdbPersonSync->syncPerson($person->getTmdbId());

        return Response::createOk();
    }

    public function renderPage(Request $request) : Response
    {
        $userId = $this->userApi->fetchUserByName((string)$request->getRouteParameters()['username'])->getId();
        $personId = (int)$request->getRouteParameters()['id'];

        $person = $this->personApi->findById($personId);

        if ($person === null) {
            return Response::createNotFound();
        }

        $birthDate = $person->getBirthDate();
        $deathDate = $person->getDeathDate();

        $age = null;
        if ($birthDate !== null) {
            if ($deathDate !== null) {
                $age = $birthDate->getDifferenceInYears($deathDate);
            } else {
                $age = $birthDate->getDifferenceInYears(Date::create());
            }
        }

        $imdbId = $person->getImdbId();

        $imdbUrl = null;
        if ($imdbId !== null) {
            $imdbUrl = $this->imdbUrlGenerator->generatePersonUrl($imdbId);
        }

        $isHiddenInTopLists = false;
        if ($this->authenticationService->isUserAuthenticatedWithCookie() === true) {
            $userId = $this->authenticationService->getCurrentUserId();
            $isHiddenInTopLists = $this->userApi->hasHiddenPerson($userId, $personId);
        }

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/person.html.twig', [
                'users' => $this->userPageAuthorizationChecker->fetchAllHavingWatchedMovieWithPersonVisibleUsernamesForCurrentVisitor($personId),
                'person' => [
                    'id' => $person->getId(),
                    'name' => $person->getName(),
                    'posterPath' => $this->urlGenerator->generateImageSrcUrlFromParameters($person->getTmdbPosterPath(), $person->getPosterPath()),
                    'knownForDepartment' => $person->getKnownForDepartment(),
                    'gender' => $person->getGender(),
                    'age' => $age,
                    'biography' => $person->getBiography(),
                    'birthDate' => $person->getBirthDate(),
                    'deathDate' => $person->getDeathDate(),
                    'placeOfBirth' => $person->getPlaceOfBirth(),
                    'tmdbUrl' => $this->tmdbUrlGenerator->generatePersonUrl($person->getTmdbId()),
                    'imdbUrl' => $imdbUrl,
                    'isHiddenInTopLists' => $isHiddenInTopLists,
                ],
                'moviesAsActor' => $this->movieApi->fetchWithActor($personId, $userId),
                'moviesFromWatchlistAsActor' => $this->movieApi->fetchFromWatchlistWithActor($personId, $userId),
                'moviesAsDirector' => $this->movieApi->fetchWithDirector($personId, $userId),
                'moviesFromWatchlistAsDirector' => $this->movieApi->fetchFromWatchlistWithDirector($personId, $userId),
            ]),
        );
    }

    public function showInTopLists(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $personId = (int)$request->getRouteParameters()['id'];

        $person = $this->personApi->findById($personId);
        if ($person === null) {
            return Response::createNotFound();
        }

        $this->personApi->updateHideInTopLists($userId, $person->getId(), false);

        return Response::createOk();
    }
}
