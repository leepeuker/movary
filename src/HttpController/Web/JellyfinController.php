<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Exception;
use Movary\Api\Jellyfin\Exception\JellyfinInvalidAuthentication;
use Movary\Api\Jellyfin\Exception\JellyfinNotFoundError;
use Movary\Api\Jellyfin\Exception\JellyfinServerConnectionError;
use Movary\Api\Jellyfin\Exception\JellyfinServerUrlMissing;
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
        $userId = $this->authenticationService->getCurrentUserId();

        $username = Json::decode($request->getBody())['username'];
        $password = Json::decode($request->getBody())['password'];

        if (empty($username) || empty($password)) {
            return Response::createBadRequest('Could not authenticate: Username or password missing');
        }

        try {
            $jellyfinAuthentication = $this->jellyfinApi->createJellyfinAuthentication($userId, $username, $password);
        } catch (JellyfinServerUrlMissing) {
            return Response::createBadRequest('Could not authenticate: Server url missing');
        } catch (JellyfinNotFoundError) {
            return Response::createBadRequest('Could not authenticate: Page not found');
        } catch (JellyfinServerConnectionError) {
            return Response::createBadRequest('Could not authenticate: Cannot connect to server');
        } catch (JellyfinInvalidAuthentication) {
            return Response::createBadRequest('Could not authenticate');
        }

        $this->userApi->updateJellyfinAuthentication($userId, $jellyfinAuthentication);

        return Response::createOk();
    }

    public function deleteJellyfinWebhookUrl() : Response
    {
        $this->userApi->deleteJellyfinWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createOk();
    }

    /**
     * @deprecated
     * @see \Movary\HttpController\Api\JellyfinController::handleJellyfinWebhook()
     */
    public function handleJellyfinWebhook(Request $request) : Response
    {
        $webhookId = $request->getRouteParameters()['id'];

        $userId = $this->userApi->findUserIdByJellyfinWebhookId($webhookId);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $requestPayload = $request->getBody();

        $this->logger->debug('Jellyfin: Webhook triggered with payload: ' . $requestPayload);
        $this->logger->warning('This jellyfin webhook url is deprecated and will stop to work soon, regenerate the url');

        $this->jellyfinScrobbler->processJellyfinWebhook($userId, Json::decode($requestPayload));

        return Response::createOk();
    }

    public function regenerateJellyfinWebhookUrl() : Response
    {
        $webhookId = $this->userApi->regenerateJellyfinWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createJson(Json::encode(['url' => $this->webhookUrlBuilder->buildJellyfinWebhookUrl($webhookId)]));
    }

    public function removeJellyfinAuthentication() : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();
        $jellyfinAuthentication = $this->userApi->findJellyfinAuthentication($userId);

        if ($jellyfinAuthentication === null) {
            return Response::createOk();
        }

        try {
            $this->jellyfinApi->deleteJellyfinAccessToken($jellyfinAuthentication);
        } catch (Exception) {
            $this->logger->warning('Could not delete jellyfin remote access token for user: ' . $userId);
        }

        $this->userApi->deleteJellyfinAuthentication($userId);

        $this->logger->info('Jellyfin authentication has been removed');

        return Response::createOk();
    }

    public function saveJellyfinServerUrl(Request $request) : Response
    {
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

    public function saveJellyfinSyncOptions(Request $request) : Response
    {
        $syncWatches = Json::decode($request->getBody())['syncWatches'];
        $userId = $this->authenticationService->getCurrentUserId();

        $this->userApi->updateJellyfinSyncEnabled($userId, $syncWatches);

        return Response::createOk();
    }

    public function verifyJellyfinServerUrl(Request $request) : Response
    {
        $jellyfinServerUrl = Url::createFromString(Json::decode($request->getBody())['jellyfinServerUrl']);
        $jellyfinAuthentication = $this->userApi->findJellyfinAuthentication($this->authenticationService->getCurrentUserId());

        $authenticationVerified = $jellyfinAuthentication !== null;
        $jellyfinServerInfo = null;

        try {
            if ($jellyfinAuthentication === null) {
                $jellyfinServerInfo = $this->jellyfinApi->fetchJellyfinServerInfoPublic($jellyfinServerUrl);
            } else {
                $jellyfinServerInfo = $this->jellyfinApi->fetchJellyfinServerInfo($jellyfinServerUrl, $jellyfinAuthentication->getAccessToken());
            }
        } catch (JellyfinNotFoundError) {
            return Response::createBadRequest('Connection test failed: Page not found');
        } catch (JellyfinServerConnectionError) {
            return Response::createBadRequest('Connection test failed: Cannot connect to server');
        } catch (JellyfinInvalidAuthentication) {
            $authenticationVerified = false;
        }

        if ($jellyfinServerInfo === null || empty($jellyfinServerInfo['Id']) === true) {
            return Response::createBadRequest('Connection test failed: Jellyfin response invalid');
        }

        return Response::createJson(Json::encode(['serverUrlVerified' => true, 'authenticationVerified' => $authenticationVerified]));
    }
}
