<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Api\Plex\Dto\PlexUserClientConfiguration;
use Movary\Api\Plex\PlexApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\Plex\PlexScrobbler;
use Movary\Service\WebhookUrlBuilder;
use Movary\Util\Json;
use Movary\ValueObject\Exception\ConfigNotSetException;
use Movary\ValueObject\Exception\InvalidUrl;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\Url;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PlexController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly PlexScrobbler $plexScrobbler,
        private readonly PlexApi $plexApi,
        private readonly WebhookUrlBuilder $webhookUrlBuilder,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function deletePlexWebhookUrl() : Response
    {
        $this->userApi->deletePlexWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createOk();
    }

    public function generatePlexAuthenticationUrl() : Response
    {
        $plexAccessToken = $this->userApi->findPlexAccessToken($this->authenticationService->getCurrentUserId());
        if ($plexAccessToken !== null) {
            return Response::createBadRequest('User is already authenticated');
        }

        try {
            $plexAuthenticationUrl = $this->plexApi->generatePlexAuthenticationUrl();
        } catch (ConfigNotSetException $e) {
            return Response::createBadRequest($e->getMessage());
        }

        return Response::createJson(Json::encode(['authenticationUrl' => $plexAuthenticationUrl]));
    }

    /**
     * @deprecated
     * @see \Movary\HttpController\Api\PlexController::handlePlexWebhook()
     */
    public function handlePlexWebhook(Request $request) : Response
    {
        $webhookId = $request->getRouteParameters()['id'];

        $userId = $this->userApi->findUserIdByPlexWebhookId($webhookId);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $requestPayload = $request->getPostParameters()['payload'] ?? null;
        if ($requestPayload === null) {
            return Response::createOk();
        }

        $this->logger->debug('Plex: Webhook triggered with payload: ' . $requestPayload);
        $this->logger->warning('This plex webhook url is deprecated and will stop to work soon, regenerate the url');

        $this->plexScrobbler->processPlexWebhook($userId, Json::decode((string)$requestPayload));

        return Response::createOk();
    }

    public function processPlexCallback() : Response
    {
        $plexClientId = $this->userApi->findPlexClientId($this->authenticationService->getCurrentUserId());
        $plexClientCode = $this->userApi->findTemporaryPlexCode($this->authenticationService->getCurrentUserId());
        if ($plexClientId === null || $plexClientCode === null) {
            throw new RuntimeException('Missing plex client id or code');
        }

        $plexAccessToken = $this->plexApi->findPlexAccessToken($plexClientId, $plexClientCode);
        if ($plexAccessToken === null) {
            throw new RuntimeException('Missing plex client id or code');
        }

        $this->userApi->updatePlexAccessToken($this->authenticationService->getCurrentUserId(), (string)$plexAccessToken);

        $plexAccount = $this->plexApi->findPlexAccount($plexAccessToken);
        if ($plexAccount !== null) {
            $plexAccountId = $plexAccount->getPlexId();
            $this->userApi->updatePlexAccountId($this->authenticationService->getCurrentUserId(), (string)$plexAccountId);
        }

        return Response::createSeeOther('/settings/integrations/plex');
    }

    public function regeneratePlexWebhookUrl() : Response
    {
        $webhookId = $this->userApi->regeneratePlexWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createJson(Json::encode(['url' => $this->webhookUrlBuilder->buildPlexWebhookUrl($webhookId)]));
    }

    public function removePlexAccessTokens() : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $this->userApi->updatePlexAccessToken($userId, null);
        $this->userApi->updatePlexClientId($userId, null);
        $this->userApi->updatePlexAccountId($userId, null);
        $this->userApi->updateTemporaryPlexClientCode($userId, null);

        return Response::create(StatusCode::createSeeOther(), null, [Header::createLocation($_SERVER['HTTP_REFERER'])]);
    }

    public function savePlexServerUrl(Request $request) : Response
    {
        $userId = $this->authenticationService->getCurrentUserId();

        $plexServerUrl = Json::decode($request->getBody())['plexServerUrl'];
        if (empty($plexServerUrl)) {
            $this->userApi->updatePlexServerUrl($userId, null);

            return Response::createOk();
        }

        try {
            $plexServerUrl = Url::createFromString($plexServerUrl);
        } catch (InvalidUrl) {
            return Response::createBadRequest('Url not properly formatted');
        }

        $this->userApi->updatePlexServerUrl($userId, $plexServerUrl);

        return Response::createOk();
    }

    public function verifyPlexServerUrl(Request $request) : Response
    {
        $plexAccessToken = $this->authenticationService->getCurrentUser()->getPlexAccessToken();
        if ($plexAccessToken === null) {
            return Response::createBadRequest('Plex authentication is missing');
        }

        $plexServerUrl = Json::decode($request->getBody())['plexServerUrl'] ?? '';

        try {
            $plexServerUrl = Url::createFromString($plexServerUrl);
        } catch (InvalidUrl) {
            return Response::createBadRequest('Provided server url is not a valid url');
        }

        $userClientConfiguration = PlexUserClientConfiguration::create($plexAccessToken, $plexServerUrl);

        return Response::createJson(Json::encode($this->plexApi->testUserClientConfiguration($userClientConfiguration)));
    }
}
