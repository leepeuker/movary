<?php declare(strict_types=1);

namespace Movary\Service;

class WebhookUrlBuilder
{
    public function __construct(private ServerSettings $serverSettings)
    {
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
        $applicationUrl = $this->serverSettings->getApplicationUrl();

        if ($applicationUrl === null) {
            return null;
        }

        return rtrim($applicationUrl, '/') . '/api/webhook/' . $webhookType . '/' . $webhookId;
    }
}
