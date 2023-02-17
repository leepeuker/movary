<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Api\Plex\Dto\PlexAccessToken;
use Movary\Api\Plex\Dto\PlexItemList;
use Movary\Api\Plex\PlexApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\Plex\PlexHistoryImporter;
use Movary\Service\Plex\PlexScrobbler;
use Movary\Util\Json;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Psr\Log\LoggerInterface;

class PlexController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly PlexApi $plexApi,
        private readonly PlexScrobbler $plexScrobbler,
        private readonly LoggerInterface $logger,
        private readonly SessionWrapper $sessionWrapper,
        private readonly PlexHistoryImporter $plexHistoryImporter
    ) {
    }

    public function deletePlexWebhookId() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $this->userApi->deletePlexWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createOk();
    }

    public function getPlexWebhookId() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $plexWebhookId = $this->userApi->findPlexWebhookId($_SESSION['userId']);

        return Response::createJson(Json::encode(['id' => $plexWebhookId]));
    }

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

        $this->plexScrobbler->processPlexWebhook($userId, Json::decode((string)$requestPayload));

        return Response::createOk();
    }

    public function regeneratePlexWebhookId() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $plexWebhookId = $this->userApi->regeneratePlexWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createJson(Json::encode(['id' => $plexWebhookId]));
    }
    
    public function processPlexCallback() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $plexClientId = $this->userApi->findPlexClientId($this->authenticationService->getCurrentUserId());
        $plexClientCode = $this->userApi->findTemporaryPlexCode($this->authenticationService->getCurrentUserId());
        if($plexClientId === null || $plexClientCode === null) {
            return Response::createSeeOther('/');
        }
        
        $plexAccessToken = $this->plexApi->fetchPlexAccessToken($plexClientId, $plexClientCode);
        if($plexAccessToken === null) {
            return Response::createSeeOther('/');
        }
        $this->userApi->updatePlexAccessToken($this->authenticationService->getCurrentUserId(), $plexAccessToken->getPlexAccessTokenAsString());
        $plexAccountId = $this->plexApi->fetchPlexAccount($plexAccessToken)->getPlexId();
        $this->userApi->updatePlexAccountId($this->authenticationService->getCurrentUserId(), (string)$plexAccountId);

        return Response::createSeeOther('/settings/plex');
    }

    public function savePlexServerUrl(Request $request) : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $plexAccessToken = $this->userApi->findPlexAccessToken($this->authenticationService->getCurrentUserId());
        if($plexAccessToken === null) {
            return Response::createSeeOther('/');
        }

        $plexServerUrl = $request->getPostParameters()['plexServerUrlInput'];
        if(!$this->plexApi->verifyPlexUrl($plexServerUrl, PlexAccessToken::createPlexAccessToken($plexAccessToken))) {
            $this->sessionWrapper->set('serverUrlStatus', false);
        } else {
            $this->sessionWrapper->set('serverUrlStatus', true);
            $this->userApi->updatePlexServerurl($this->authenticationService->getCurrentUserId(), $plexServerUrl);
        }
        return Response::create(StatusCode::createSeeOther(), null, [Header::createLocation($_SERVER['HTTP_REFERER'])]);
    }

    public function removePlexAccessTokens() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }

        $this->userApi->updatePlexAccessToken($this->authenticationService->getCurrentUserId(), null);
        $this->userApi->updatePlexClientId($this->authenticationService->getCurrentUserId(), null);
        $this->userApi->updateTemporaryPlexClientCode($this->authenticationService->getCurrentUserId(), null);
        return Response::create(StatusCode::createSeeOther(), null, [Header::createLocation($_SERVER['HTTP_REFERER'])]);
    }

    public function importPlexHistory() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createSeeOther('/');
        }
        $userId = $this->authenticationService->getCurrentUserId();
        $unknownPlexItems = $this->plexHistoryImporter->importPlexData($userId);
        return Response::createJson($unknownPlexItems);
    }
}