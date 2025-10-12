<?php declare(strict_types=1);

namespace Movary\Service;

use Movary\ValueObject\RelativeUrl;

class WebhookUrlBuilder
{
    public function __construct(
        private readonly ApplicationUrlService $applicationUrlService,
    ) {
    }

    public function buildEmbyWebhookUrl(string $webhookId) : ?string
    {
        return $this->buildUrl('emby', $webhookId);
    }

    public function buildJellyfinWebhookUrl(string $webhookId) : ?string
    {
        return $this->buildUrl('jellyfin', $webhookId);
    }

    public function buildKodiWebhookUrl(string $webhookId) : ?string
    {
        return $this->buildUrl('kodi', $webhookId);
    }

    public function buildPlexWebhookUrl(string $webhookId) : ?string
    {
        return $this->buildUrl('plex', $webhookId);
    }

    private function buildUrl(string $webhookType, string $webhookId) : ?string
    {
        if ($this->applicationUrlService->hasApplicationUrl() === false) {
            return null;
        }

        return $this->applicationUrlService->createApplicationUrl(
            RelativeUrl::create('/api/webhook/' . $webhookType . '/' . $webhookId),
        );
    }
}
