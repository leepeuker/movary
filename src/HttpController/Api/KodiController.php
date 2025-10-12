<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\UserApi;
use Movary\Service\Kodi\KodiScrobbler;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Psr\Log\LoggerInterface;

class KodiController
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly KodiScrobbler $kodiScrobbler,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handleKodiWebhook(Request $request) : Response
    {
        $webhookId = $request->getRouteParameters()['id'];

        $userId = $this->userApi->findUserIdByKodiWebhookId($webhookId);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $requestPayload = $request->getBody();

        $this->logger->debug('Kodi: Webhook triggered with payload: ' . $requestPayload);

        $this->kodiScrobbler->processKodiWebhook($userId, Json::decode($requestPayload));

        return Response::createOk();
    }
}
