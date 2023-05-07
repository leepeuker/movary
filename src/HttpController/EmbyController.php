<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\Emby\EmbyScrobbler;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Psr\Log\LoggerInterface;

class EmbyController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly EmbyScrobbler $embyScrobbler,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function deleteEmbyWebhookId() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $this->userApi->deleteEmbyWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createOk();
    }

    public function getEmbyWebhookId() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $webhookId = $this->userApi->findEmbyWebhookId($_SESSION['userId']);

        return Response::createJson(Json::encode(['id' => $webhookId]));
    }

    public function handleEmbyWebhook(Request $request) : Response
    {
        $webhookId = $request->getRouteParameters()['id'];

        $userId = $this->userApi->findUserIdByEmbyWebhookId($webhookId);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $requestPayload = $request->getPostParameters()['data'];

        $this->logger->debug('Emby: Webhook triggered with payload: ' . $requestPayload);

        $this->embyScrobbler->processEmbyWebhook($userId, Json::decode($requestPayload));

        return Response::createOk();
    }

    public function regenerateEmbyWebhookId() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $plexWebhookId = $this->userApi->regenerateEmbyWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createJson(Json::encode(['id' => $plexWebhookId]));
    }
}
