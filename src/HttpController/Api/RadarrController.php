<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\UserApi;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class RadarrController
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly MovieWatchlistApi $movieWatchlistApi,
    ) {
    }

    public function renderRadarrFeed(Request $request) : Response
    {
        $feedId = $request->getRouteParameters()['id'];

        $userId = $this->userApi->findUserIdByRadarrFeedId($feedId);

        if ($userId === null) {
            return Response::createNotFound();
        }

        $response = Json::encode($this->movieWatchlistApi->fetchAllWatchlistItems($userId));

        return Response::createJson($response);
    }
}
