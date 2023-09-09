<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\WebhookUrlBuilder;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class RadarrController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly MovieWatchlistApi $movieWatchlistApi,
        private readonly WebhookUrlBuilder $webhookUrlBuilder,
    ) { }
    
    public function deleteRadarrFeedId() : Response
    {
        $this->userApi->deleteRadarrFeedId($this->authenticationService->getCurrentUserId());

        return Response::createOk();
    }

    public function regenerateRadarrFeedId() : Response
    {
        $feedId = $this->userApi->regenerateRadarrFeedId($this->authenticationService->getCurrentUserId());

        return Response::createJson(Json::encode(['url' => $this->webhookUrlBuilder->buildRadarrFeedUrl($feedId)]));
    }

    public function renderRadarrFeed(Request $request) : Response
    {
        $feedId = $request->getRouteParameters()['id'];

        $userId = $this->userApi->findUserIdByRadarrFeedId($feedId);

        if($userId === null) {
            return Response::createNotFound();
        }
        $watchlist = $this->movieWatchlistApi->fetchAllWatchlistItems($userId);

        $response = Json::encode($watchlist);

        return Response::createJson($response);
    }
}