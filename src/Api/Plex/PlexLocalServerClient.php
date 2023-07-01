<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use Movary\Api\Plex\Exception\PlexAuthenticationError;
use Movary\Api\Plex\Exception\PlexNotFoundError;
use Movary\Service\ServerSettings;
use Movary\Util\Json;
use Movary\ValueObject\Url;
use RuntimeException;

class PlexLocalServerClient
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

    /**
     * @psalm-suppress PossiblyUndefinedVariable
     */
    public function sendGetRequest(
        Url $requestUrl,
        ?array $customQuery = [],
    ) : array {
        $requestOptions = [
            'form_params' => $this->generateDefaultFormData(),
            'query' => $customQuery,
            'headers' => self::DEFAULT_HEADERS,
            'connect_timeout' => 2,
        ];

        try {
            $response = $this->httpClient->request('GET', (string)$requestUrl, $requestOptions);
        } catch (ClientException $e) {
            match (true) {
                $e->getCode() === 401 => throw PlexAuthenticationError::create(),
                $e->getCode() === 404 => throw PlexNotFoundError::create($requestUrl),
                default => throw new RuntimeException('Plex API error. Response message: ' . $e->getMessage()),
            };
        }

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
