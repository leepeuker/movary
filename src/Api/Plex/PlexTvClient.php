<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use Movary\Api\Plex\Dto\PlexAccessToken;
use Movary\Api\Plex\Exception\PlexAuthenticationInvalid;
use Movary\Api\Plex\Exception\PlexNotFoundError;
use Movary\Service\ServerSettings;
use Movary\Util\Json;
use Movary\ValueObject\RelativeUrl;
use Movary\ValueObject\Url;
use RuntimeException;

class PlexTvClient
{
    private const APP_NAME = 'Movary';

    private const DEFAULT_HEADERS = [
        'accept' => 'application/json'
    ];

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly ServerSettings $serverSettings,
    ) {
    }

    public function get(RelativeUrl $relativeUrl, array $headers) : array
    {
        $requestUrl = Url::createFromString('https://plex.tv/api/v2')->appendRelativeUrl($relativeUrl);
        $requestOptions = [
            'form_params' => $this->generateDefaultFormData(),
            'headers' => array_merge(self::DEFAULT_HEADERS, $headers)
        ];

        try {
            $response = $this->httpClient->request('GET', (string)$requestUrl, $requestOptions);
        } catch (ClientException $e) {
            match (true) {
                $e->getCode() === 401 => throw PlexAuthenticationInvalid::create(),
                $e->getCode() === 404 => throw PlexNotFoundError::create($requestUrl),

                default => throw new RuntimeException('Plex API error. Response message: ' . $e->getMessage()),
            };
        }

        /** @psalm-suppress PossiblyUndefinedVariable */
        return Json::decode((string)$response->getBody());
    }

    public function getMetadata(
        PlexAccessToken $plexAccessToken,
        RelativeUrl $relativeUrl,
        array $query = [],
        int $limit = null,
        int $offset = null,
    ) : array {
        $requestUrl = Url::createFromString('https://metadata.provider.plex.tv/')->appendRelativeUrl($relativeUrl);
        $requestOptions = [
            'query' => array_merge([
                'X-Plex-Token' => (string)$plexAccessToken,
                'X-Plex-Container-Size' => (string)$limit,
                'X-Plex-Container-Start' => (string)$offset
            ], $query),
            'headers' => self::DEFAULT_HEADERS,
        ];

        try {
            $response = $this->httpClient->request('GET', (string)$requestUrl, $requestOptions);
        } catch (ClientException $e) {
            match (true) {
                $e->getCode() === 401 => throw PlexAuthenticationInvalid::create(),
                $e->getCode() === 404 => throw PlexNotFoundError::create($requestUrl),

                default => throw new RuntimeException('Plex API error. Response message: ' . $e->getMessage()),
            };
        }

        /** @psalm-suppress PossiblyUndefinedVariable */
        return Json::decode((string)$response->getBody());
    }

    public function post(RelativeUrl $relativeUrl) : array
    {
        $requestUrl = Url::createFromString('https://plex.tv/api/v2')->appendRelativeUrl($relativeUrl);
        $requestOptions = [
            'form_params' => $this->generateDefaultFormData(),
            'headers' => self::DEFAULT_HEADERS
        ];

        try {
            $response = $this->httpClient->request('POST', (string)$requestUrl, $requestOptions);
        } catch (ClientException $e) {
            match (true) {
                $e->getCode() === 401 => throw PlexAuthenticationInvalid::create(),
                $e->getCode() === 404 => throw PlexNotFoundError::create($requestUrl),

                default => throw new RuntimeException('Plex API error. Response message: ' . $e->getMessage()),
            };
        }

        /** @psalm-suppress PossiblyUndefinedVariable */
        return Json::decode((string)$response->getBody());
    }

    private function generateDefaultFormData() : array
    {
        $plexIdentifier = $this->serverSettings->requirePlexIdentifier();

        return [
            'X-Plex-Client-Identifier' => $plexIdentifier,
            'X-Plex-Product' => self::APP_NAME,
            'X-Plex-Product-Version' => $plexIdentifier,
            'X-Plex-Platform' => php_uname('s'),
            'X-Plex-Platform-Version' => php_uname('v'),
            'X-Plex-Provides' => 'Controller',
            'strong' => 'true'
        ];
    }
}
