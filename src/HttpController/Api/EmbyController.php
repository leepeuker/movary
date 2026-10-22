<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\User\UserApi;
use Movary\HttpController\Api\RequestMapper\EmbyWebhookRequestMapper;
use Movary\Service\Emby\EmbyScrobbler;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class EmbyController
{
    public function __construct(
        private readonly UserApi $userApi,
        private readonly EmbyScrobbler $embyScrobbler,
        private readonly EmbyWebhookRequestMapper $requestMapper,
    ) {
    }

    public function handleEmbyWebhook(Request $request) : Response
    {
        $webhookId = $request->getRouteParameters()['token'];

        $userId = $this->userApi->findUserIdByEmbyWebhookId($webhookId);
        if ($userId === null) {
            return Response::createNotFound();
        }

        $this->embyScrobbler->processEmbyWebhook($userId, $this->requestMapper->mapRequest($request));

        return Response::createOk();
    }
}
