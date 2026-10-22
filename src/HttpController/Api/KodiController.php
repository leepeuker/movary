<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\RequestMapper\KodiWebhookRequestMapper;
use Movary\Service\Kodi\KodiScrobbler;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class KodiController
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly KodiScrobbler $kodiScrobbler,
        private readonly KodiWebhookRequestMapper $requestMapper,
    ) {
    }

    public function handleKodiWebhook(Request $request) : Response
    {
        $webhookId = $request->getRouteParameters()['token'];

        $userId = $this->userApi->findUserIdByKodiWebhookId($webhookId);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $this->kodiScrobbler->processKodiWebhook($userId, $this->requestMapper->mapRequest($request));

        return Response::createOk();
    }
}
