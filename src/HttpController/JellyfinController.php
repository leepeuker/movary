<?php declare(strict_types=1);

namespace Movary\HttpController;

use Exception;
use Movary\Api\Jellyfin\JellyfinApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\Jellyfin\JellyfinScrobbler;
use Movary\Service\WebhookUrlBuilder;
use Movary\Util\Json;
use Movary\ValueObject\Exception\InvalidUrl;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Url;
use Psr\Log\LoggerInterface;

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

    public function authenticateJellyfinAccount(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $userId = $this->authenticationService->getCurrentUserId();

        if (empty($this->userApi->findJellyfinServerUrl($userId))) {
            return Response::createBadRequest();
        }

        $username = Json::decode($request->getBody())['username'];
        $password = Json::decode($request->getBody())['password'];

        if (empty($username) || empty($password)) {
            return Response::createBadRequest();
        }

        $jellyfinAuthentication = $this->jellyfinApi->createJellyfinAuthentication($username, $password);
        if ($jellyfinAuthentication === null) {
            return Response::createUnauthorized();
        }

        $this->userApi->updateJellyfinAuthentication($userId, $jellyfinAuthentication);

        return Response::createOk();
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

    public function removeJellyfinAuthentication() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $this->jellyfinApi->deleteJellyfinAccessToken();
        $this->userApi->deleteJellyfinAuthentication($this->authenticationService->getCurrentUserId());

        return Response::createOk();
    }

    public function saveJellyfinServerUrl(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $jellyfinServerUrl = Json::decode($request->getBody())['JellyfinServerUrl'];
        $userId = $this->authenticationService->getCurrentUserId();

        if (empty($jellyfinServerUrl)) {
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
            $jellyfinServerInfo = $this->jellyfinApi->fetchJellyfinServerInfo();
            if ($jellyfinServerInfo === null) {
                return Response::createBadRequest();
            } elseif ($jellyfinServerInfo['ProductName'] === 'Jellyfin Server') {
                return Response::createOk();
            }

            return Response::createBadRequest();
        } catch (Exception) {
            return Response::createBadRequest();
        }
    }
}
