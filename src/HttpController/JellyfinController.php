<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\Jellyfin\JellyfinScrobbler;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Psr\Log\LoggerInterface;

class JellyfinController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly JellyfinScrobbler $jellyfinScrobbler,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function deleteJellyfinWebhookId() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $this->userApi->deleteJellyfinWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createOk();
    }

    public function getJellyfinWebhookId() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $plexWebhookId = $this->userApi->findJellyfinWebhookId($_SESSION['userId']);

        return Response::createJson(Json::encode(['id' => $plexWebhookId]));
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

    public function regenerateJellyfinWebhookId() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $plexWebhookId = $this->userApi->regenerateJellyfinWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createJson(Json::encode(['id' => $plexWebhookId]));
    }
}
