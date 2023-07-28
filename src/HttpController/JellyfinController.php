<?php declare(strict_types=1);

namespace Movary\HttpController;

use Exception;
use Movary\Api\Jellyfin\JellyfinApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\Jellyfin\JellyfinScrobbler;
use Movary\Service\WebhookUrlBuilder;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Psr\Log\LoggerInterface;
use Movary\ValueObject\Url;
use Movary\ValueObject\Exception\InvalidUrl;
use Movary\ValueObject\Http\StatusCode;

class JellyfinController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly JellyfinScrobbler $jellyfinScrobbler,
        private readonly WebhookUrlBuilder $webhookUrlBuilder,
        private readonly LoggerInterface $logger,
        private readonly JellyfinApi $jellyfinApi,
    ) {
    }

    public function deleteJellyfinWebhookUrl() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $this->userApi->deleteJellyfinWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createOk();
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

    public function regenerateJellyfinWebhookUrl() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $webhookId = $this->userApi->regenerateJellyfinWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createJson(Json::encode(['url' => $this->webhookUrlBuilder->buildJellyfinWebhookUrl($webhookId)]));
    }

    public function saveJellyfinServerUrl(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $jellyfinServerUrl = Json::decode($request->getBody())['JellyfinServerUrl'];
        $userId = $this->authenticationService->getCurrentUserId();

        if(empty($jellyfinServerUrl)) {
            $this->userApi->updateJellyfinServerUrl($userId, null);

            return Response::createOk();
        }

        try {
            $jellyfinServerUrl = Url::createFromString($jellyfinServerUrl);
        } catch (InvalidUrl) {
            return Response::createBadRequest('Provided server url is not a valid url');
        }

        $this->userApi->updateJellyfinServerUrl($userId, $jellyfinServerUrl);
        return Response::createOk();
    }

    public function verifyJellyfinServerUrl() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        try {
            $this->jellyfinApi->fetchJellyfinServerInfo();
            return Response::createOk();
        } catch (Exception) {
            return Response::createBadRequest();
        }

    }
}
