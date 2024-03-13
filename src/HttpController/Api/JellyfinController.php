<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\UserApi;
use Movary\Service\Jellyfin\JellyfinScrobbler;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Psr\Log\LoggerInterface;

class JellyfinController
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly JellyfinScrobbler $jellyfinScrobbler,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handleJellyfinWebhook(Request $request) : Response
    {
        $webhookId = $request->getRouteParameters()['id'];

        $userId = $this->userApi->findUserIdByJellyfinWebhookId($webhookId);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $requestPayload = $request->getBody();

        $this->logger->debug('Jellyfin: Webhook triggered with payload: ' . $requestPayload);

        $this->jellyfinScrobbler->processJellyfinWebhook($userId, Json::decode($requestPayload));

        return Response::createOk();
    }
}
