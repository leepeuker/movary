<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\RequestMapper\JellyfinWebhookRequestMapper;
use Movary\Service\Jellyfin\JellyfinScrobbler;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class JellyfinController
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly JellyfinScrobbler $jellyfinScrobbler,
        private readonly JellyfinWebhookRequestMapper $requestMapper,
    ) {
    }

    public function handleJellyfinWebhook(Request $request) : Response
    {
        $webhookId = $request->getRouteParameters()['id'];

        $userId = $this->userApi->findUserIdByJellyfinWebhookId($webhookId);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $this->jellyfinScrobbler->processJellyfinWebhook($userId, $this->requestMapper->mapRequest($request));

        return Response::createOk();
    }
}
