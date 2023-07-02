<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use Movary\Api\Plex\Dto\PlexUserClientConfiguration;
use Movary\ValueObject\RelativeUrl;

class PlexUserClient extends PlexClient
{
    public function get(
        PlexUserClientConfiguration $clientConfiguration,
        ?RelativeUrl $relativeUrl = null,
    ) : array {
        $requestOptions = [
            'headers' => array_merge(
                self::DEFAULT_HEADERS,
                ['X-Plex-Token' => (string)$clientConfiguration->getAccessToken()],
            ),
        ];

        $requestUrl = $clientConfiguration->getServerUrl();
        if ($relativeUrl !== null) {
            $requestUrl = $requestUrl->appendRelativeUrl($relativeUrl);
        }

        return $this->sendRequest('GET', $requestUrl, $requestOptions);
    }
}
