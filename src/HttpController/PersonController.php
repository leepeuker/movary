<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie;
use Movary\Application\Person;
use Movary\Application\User;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class PersonController
{
    public function __construct(
        private readonly Person\Api $personApi,
        private readonly Movie\Api $movieApi,
        private readonly User\Api $userApi,
        private readonly Environment $twig,
    ) {
    }

    public function renderPage(Request $request) : Response
    {
        $userId = $this->userApi->findUserByName((string)$request->getRouteParameters()['username'])?->getId();
        if ($userId === null) {
            return Response::createNotFound();
        }

        $personId = (int)$request->getRouteParameters()['id'];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/person.html.twig', [
                'users' => $this->userApi->fetchAllHavingWatchedMoviesWithPerson($personId),
                'person' => $this->personApi->findById($personId),
                'moviesAsActor' => $this->movieApi->fetchWithActor($personId, $userId),
                'moviesAsDirector' => $this->movieApi->fetchWithDirector($personId, $userId),
            ]),
        );
    }
}
