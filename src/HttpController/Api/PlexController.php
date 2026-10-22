<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\RequestMapper\PlexWebhookRequestMapper;
use Movary\Service\Plex\PlexScrobbler;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class PlexController
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly PlexScrobbler $plexScrobbler,
        private readonly PlexWebhookRequestMapper $requestMapper,
    ) {
    }

    public function handlePlexWebhook(Request $request) : Response
    {
        $webhookId = $request->getRouteParameters()['token'];

        $userId = $this->userApi->findUserIdByPlexWebhookId($webhookId);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $this->plexScrobbler->processPlexWebhook($userId, $this->requestMapper->mapRequest($request));

        return Response::createOk();
    }
}
