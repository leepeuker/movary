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

    public function buildPlexWebhookUrl(string $webhookId) : ?string
    {
        return $this->buildUrl('plex', $webhookId);
    }

    public function buildRadarrFeedUrl(string $feedId) : ?string
    {
        return $this->buildUrl('api/feed/radarr', $feedId);
    }

    private function buildUrl(string $webhookType, string $webhookId) : ?string
    {
        $applicationUrl = $this->serverSettings->getApplicationUrl();

        if ($applicationUrl === null) {
            return null;
        }

        return rtrim($applicationUrl, '/') . '/' . $webhookType . '/' . $webhookId;
    }
}
