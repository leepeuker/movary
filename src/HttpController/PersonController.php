<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie;
use Movary\Application\Person;
use Movary\Util\Json;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Twig\Environment;

class PersonController
{
    public function __construct(
        private readonly Person\Api $personApi,
        private readonly Environment $twig,
    ) {
    }

    public function fetchWatchedMoviesActedBy(Request $request) : Response
    {
        $personId = (int)$request->getRouteParameters()['id'];

        return Response::create(
            StatusCode::createOk(),
            Json::encode($this->personApi->findWatchedMoviesActedBy($personId)),
            [Header::createContentTypeJson()]
        );
    }

    public function fetchWatchedMoviesDirectedBy(Request $request) : Response
    {
        $personId = (int)$request->getRouteParameters()['id'];

        return Response::create(
            StatusCode::createOk(),
            Json::encode($this->personApi->findWatchedMoviesDirectedBy($personId)),
            [Header::createContentTypeJson()]
        );
    }

    public function renderPage(Request $request) : Response
    {
        $personId = (int)$request->getRouteParameters()['id'];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/actor.html.twig', [
                'person' => $this->personApi->findById($personId),
                'moviesAsActor' => $this->personApi->findWatchedMoviesActedBy($personId),
                'moviesAsDirector' => $this->personApi->findWatchedMoviesDirectedBy($personId),
            ]),
        );
    }
}
