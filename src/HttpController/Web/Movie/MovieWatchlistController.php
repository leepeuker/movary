<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Movie;

use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\Authentication;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class MovieWatchlistController
{
    public function __construct(
        private readonly MovieWatchlistApi $movieWatchlistApi,
        private readonly Authentication $authenticationService,
    ) {
    }

    public function addToWatchlist(Request $request) : Response
    {
        $movieId = (int)$request->getRouteParameters()['id'];
        $userId = $this->authenticationService->getCurrentUser()->getId();

        $this->movieWatchlistApi->addMovieToWatchlist($userId, $movieId);

        return Response::createOk();
    }

    public function removeFromWatchlist(Request $request) : Response
    {
        $movieId = (int)$request->getRouteParameters()['id'];
        $userId = $this->authenticationService->getCurrentUser()->getId();

        $this->movieWatchlistApi->removeMovieFromWatchlist($userId, $movieId);

        return Response::createOk();
    }
}
