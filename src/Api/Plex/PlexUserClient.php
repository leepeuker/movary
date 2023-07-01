<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use Movary\Api\Plex\Dto\PlexUserClientConfiguration;
use Movary\Api\Plex\Exception\PlexAuthenticationError;
use Movary\Api\Plex\Exception\PlexNotFoundError;
use Movary\Service\ServerSettings;
use Movary\Util\Json;
use Movary\ValueObject\RelativeUrl;
use RuntimeException;

class PlexUserClient
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

    public function get(
        PlexUserClientConfiguration $clientConfiguration,
        ?RelativeUrl $relativeUrl = null,
    ) : array {
        $requestOptions = [
            'form_params' => $this->generateDefaultFormData(),
            'query' => [
                'X-Plex-Token' => (string)$clientConfiguration->getAccessToken(),
            ],
            'headers' => self::DEFAULT_HEADERS,
            'connect_timeout' => 2,
        ];

        $requestUrl = $clientConfiguration->getServerUrl();
        if ($relativeUrl !== null) {
            $requestUrl = $requestUrl->appendRelativeUrl($relativeUrl);
        }

        try {
            $response = $this->httpClient->request('GET', (string)$requestUrl, $requestOptions);
        } catch (ClientException $e) {
            match (true) {
                $e->getCode() === 401 => throw PlexAuthenticationError::create(),
                $e->getCode() === 404 => throw PlexNotFoundError::create($requestUrl),
                default => throw new RuntimeException('Plex API error. Response message: ' . $e->getMessage()),
            };
        }

        /** @psalm-suppress PossiblyUndefinedVariable */
        return Json::decode((string)$response->getBody());
    }

    private function generateDefaultFormData() : array
    {
        return [
            'X-Plex-Client-Identifier' => $this->serverSettings->requirePlexIdentifier(),
            'X-Plex-Product' => self::APP_NAME,
            'X-Plex-Product-Version' => $this->serverSettings->getApplicationVersion(),
            'X-Plex-Platform' => php_uname('s'),
            'X-Plex-Platform-Version' => php_uname('v'),
            'X-Plex-Provides' => 'Controller',
            'strong' => 'true'
        ];
    }
}
