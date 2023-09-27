<?php declare(strict_types=1);

namespace Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\Emby\EmbyScrobbler;
use Movary\Service\WebhookUrlBuilder;
use Movary\Util\Json;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Psr\Log\LoggerInterface;

class EmbyController
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly EmbyScrobbler $embyScrobbler,
        private readonly WebhookUrlBuilder $webhookUrlBuilder,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function deleteEmbyWebhookUrl() : Response
    {
        $this->userApi->deleteEmbyWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createOk();
    }

    public function regenerateEmbyWebhookUrl() : Response
    {
        $webhookId = $this->userApi->regenerateEmbyWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createJson(Json::encode(['url' => $this->webhookUrlBuilder->buildEmbyWebhookUrl($webhookId)]));
    }
}
