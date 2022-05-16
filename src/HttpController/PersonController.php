<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Person\Service\Select;
use Movary\Util\Json;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;

class PersonController
{
    public function __construct(private readonly Select $personSelectService)
    {
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

    public function fetchWatchedMoviesActedBy(Request $request) : Response
    {
        $personId = (int)$request->getRouteParameters()['id'];

        return Response::create(
            StatusCode::createOk(),
            Json::encode($this->personSelectService->findWatchedMoviesActedBy($personId)),
            [Header::createContentTypeJson()]
        );
    }
}
