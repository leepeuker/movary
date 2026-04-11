<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Exception;
use Movary\Api\Jellyfin\Exception\JellyfinInvalidAuthentication;
use Movary\Api\Jellyfin\JellyfinApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\Jellyfin\JellyfinScrobbler;
use Movary\Service\WebhookUrlBuilder;
use Movary\Util\Json;
use Movary\Util\UrlValidator;
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
        private readonly UrlValidator $urlValidator,
        private readonly bool $validateUrlIsSafe = false,
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
        } catch (Exception $e) {
            $this->logger->warning('Jellyfin could not authenticate: ' . $e->getMessage());
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
        $this->logger->warning('Jellyfin: This webhook url is deprecated and will stop to work soon, regenerate the url');

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
            $this->logger->warning('Jellyfin: Could not delete remote access token for user: ' . $userId);
        }

        $this->userApi->deleteJellyfinAuthentication($userId);

        $this->logger->info('Jellyfin authentication has been removed');

        return Response::createOk();
    }

    public function saveJellyfinServerUrl(Request $request) : Response
    {
        $jellyfinServerUrlString = Json::decode($request->getBody())['JellyfinServerUrl'];
        $userId = $this->authenticationService->getCurrentUserId();

        if (empty($jellyfinServerUrlString)) {
            $this->userApi->updateJellyfinServerUrl($userId, null);

            return Response::createOk();
        }

        try {
            $jellyfinServerUrl = Url::createFromString($jellyfinServerUrlString);
        } catch (InvalidUrl) {
            $this->logger->info('Jellyfin: Provided server url is not a valid url: ' . $jellyfinServerUrlString);
            return Response::createBadRequest('Provided server url is not a valid url');
        }

        if ($this->validateUrlIsSafe === true) {
            try {
                $this->urlValidator->validateUrlIsSafe($jellyfinServerUrl);
            } catch (InvalidUrl $e) {
                $this->logger->warning('Jellyfin: Could not safe server ur: ' . $e->getMessage());
                return Response::createBadRequest('Could not safe server url');
            }
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
        $jellyfinServerUrlString = Json::decode($request->getBody())['jellyfinServerUrl'];

        try {
            $jellyfinServerUrl = Url::createFromString($jellyfinServerUrlString);
        } catch (InvalidUrl) {
            $this->logger->info('Jellyfin: Provided server url is not a valid url: ' . $jellyfinServerUrlString);
            return Response::createBadRequest('Provided server url is not a valid url');
        }

        if ($this->validateUrlIsSafe === true) {
            try {
                $this->urlValidator->validateUrlIsSafe($jellyfinServerUrl);
            } catch (InvalidUrl $e) {
                $this->logger->warning('Jellyfin: Could not safe server ur: ' . $e->getMessage());
                return Response::createBadRequest('Could not safe server url');
            }
        }

        $jellyfinAuthentication = $this->userApi->findJellyfinAuthentication($this->authenticationService->getCurrentUserId());

        $authenticationVerified = $jellyfinAuthentication !== null;
        $jellyfinServerInfo = null;

        try {
            if ($jellyfinAuthentication === null) {
                $jellyfinServerInfo = $this->jellyfinApi->fetchJellyfinServerInfoPublic($jellyfinServerUrl);
            } else {
                $jellyfinServerInfo = $this->jellyfinApi->fetchJellyfinServerInfo($jellyfinServerUrl, $jellyfinAuthentication->getAccessToken());
            }
        } catch (JellyfinInvalidAuthentication) {
            $authenticationVerified = false;
        } catch (Exception $e) {
            $this->logger->warning('Jellyfin: Connection test failed: ' . $e->getMessage());
            return Response::createBadRequest('Connection test failed');
        }

        if ($jellyfinServerInfo === null || empty($jellyfinServerInfo['Id']) === true) {
            $this->logger->warning('Jellyfin: Connection test failed: Jellyfin response invalid');
            return Response::createBadRequest('Connection test failed');
        }

        return Response::createJson(Json::encode(['serverUrlVerified' => true, 'authenticationVerified' => $authenticationVerified]));
    }
}
