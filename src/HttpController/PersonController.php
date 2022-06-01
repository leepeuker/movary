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
        private readonly Person\Service\Select $personSelectService,
        private readonly Environment $twig,
    ) {
    }

    public function fetchWatchedMoviesActedBy(Request $request) : Response
    {
        $personId = (int)$request->getRouteParameters()['id'];

        return Response::create(
            StatusCode::createOk(),
            Json::encode($this->personSelectService->findWatchedMoviesActedBy($personId)),
            [Header::createContentTypeJson()]
        );
    }

    public function fetchWatchedMoviesDirectedBy(Request $request) : Response
    {
        $personId = (int)$request->getRouteParameters()['id'];

        return Response::create(
            StatusCode::createOk(),
            Json::encode($this->personSelectService->findWatchedMoviesDirectedBy($personId)),
            [Header::createContentTypeJson()]
        );
    }

    public function renderPage(Request $request) : Response
    {
        $personId = (int)$request->getRouteParameters()['id'];

        return Response::create(
            StatusCode::createOk(),
            $this->twig->render('page/actor.html.twig', [
                'person' => $this->personSelectService->findById($personId),
                'moviesAsActor' => $this->personSelectService->findWatchedMoviesActedBy($personId),
                'moviesAsDirector' => $this->personSelectService->findWatchedMoviesDirectedBy($personId),
            ]),
        );
    }
}
