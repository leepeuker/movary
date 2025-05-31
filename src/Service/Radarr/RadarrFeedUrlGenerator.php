<?php declare(strict_types=1);

namespace Movary\Service\Radarr;

use Movary\Service\ServerSettings;

class RadarrFeedUrlGenerator
{
    public function __construct(
        private readonly ServerSettings $serverSettings,
    ) {
    }

    public function generateUrl(string $feedId) : string
    {
        return $this->serverSettings->getApplicationUrl() . '/api/feed/radarr/' . $feedId;
    }
}
