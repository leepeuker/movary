<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use Movary\Api\Plex\Dto\PlexAccessToken;
use Movary\ValueObject\RelativeUrl;
use Movary\ValueObject\Url;

class PlexTvClient extends PlexClient
{
    public function get(RelativeUrl $relativeUrl, array $headers) : array
    {
        $requestUrl = Url::createFromString('https://plex.tv/api/v2')->appendRelativeUrl($relativeUrl);
        $requestOptions = [
            'form_params' => $this->generateDefaultFormData(),
            'headers' => array_merge(self::DEFAULT_HEADERS, $headers)
        ];

        return $this->sendRequest('GET', $requestUrl, $requestOptions);
    }

    public function getMetadata(
        PlexAccessToken $plexAccessToken,
        RelativeUrl $relativeUrl,
        array $query = [],
        ?int $limit = null,
        ?int $offset = null,
    ) : array {
        $requestUrl = Url::createFromString('https://metadata.provider.plex.tv/')->appendRelativeUrl($relativeUrl);
        $requestOptions = [
            'query' => array_merge([
                'X-Plex-Container-Size' => (string)$limit,
                'X-Plex-Container-Start' => (string)$offset
            ], $query),
            'headers' => array_merge([
                'X-Plex-Token' => (string)$plexAccessToken,
            ], self::DEFAULT_HEADERS),
        ];

        return $this->sendRequest('GET', $requestUrl, $requestOptions);
    }

    public function post(RelativeUrl $relativeUrl) : array
    {
        $requestUrl = Url::createFromString('https://plex.tv/api/v2')->appendRelativeUrl($relativeUrl);
        $requestOptions = [
            'form_params' => $this->generateDefaultFormData(),
            'headers' => self::DEFAULT_HEADERS
        ];

        return $this->sendRequest('POST', $requestUrl, $requestOptions);
    }

    private function generateDefaultFormData() : array
    {
        $plexIdentifier = $this->serverSettings->requirePlexIdentifier();

        return [
            'X-Plex-Client-Identifier' => $plexIdentifier,
            'X-Plex-Product' => $this->serverSettings->getPlexAppName(),
            'X-Plex-Product-Version' => $plexIdentifier,
            'X-Plex-Platform' => php_uname('s'),
            'X-Plex-Platform-Version' => php_uname('v'),
            'X-Plex-Provides' => 'Controller',
            'strong' => 'true'
        ];
    }
}
