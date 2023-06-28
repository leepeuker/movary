<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use Movary\Api\Plex\Exception\PlexAuthenticationError;
use Movary\Api\Plex\Exception\PlexNoClientIdentifier;
use Movary\Api\Plex\Exception\PlexNotFoundError;
use Movary\Util\Json;
use RuntimeException;

class PlexTvClient
{
    private const BASE_URL = 'https://plex.tv/api/v2';

    private const APP_NAME = 'Movary';

    private const DEFAULT_HEADERS = [
        'accept' => 'application/json'
    ];

    private array $defaultFormData;

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly ?string $plexIdentifier,
    ) {
        $this->defaultFormData = [
            'X-Plex-Client-Identifier' => $this->plexIdentifier,
            'X-Plex-Product' => self::APP_NAME,
            'X-Plex-Product-Version' => $this->plexIdentifier,
            'X-Plex-Platform' => php_uname('s'),
            'X-Plex-Platform-Version' => php_uname('v'),
            'X-Plex-Provides' => 'Controller',
            'strong' => 'true'
        ];
    }

    public function get(
        string $relativeUrl,
        ?array $headers = [],
    ) : array {
        if ($this->plexIdentifier === null) {
            throw PlexNoClientIdentifier::create();
        }

        $requestUrl = self::BASE_URL . $relativeUrl;
        $requestOptions = [
            'headers' => array_merge(self::DEFAULT_HEADERS, $headers)
        ];

        try {
            $response = $this->httpClient->request('GET', $requestUrl, $requestOptions);
        } catch (ClientException $e) {
            $this->throwConvertedClientException($e, $requestUrl);
        }

        return Json::decode((string)$response->getBody());
    }

    public function sendPostRequest(string $relativeUrl) : array
    {
        if ($this->plexIdentifier === null) {
            throw PlexNoClientIdentifier::create();
        }

        $requestUrl = self::BASE_URL . $relativeUrl;
        $requestOptions = [
            'form_params' => $this->defaultFormData,
            'headers' => self::DEFAULT_HEADERS
        ];

        try {
            $response = $this->httpClient->request('POST', $requestUrl, $requestOptions);
        } catch (ClientException $e) {
            $this->throwConvertedClientException($e, $requestUrl);
        }

        return Json::decode((string)$response->getBody());
    }

    private function throwConvertedClientException(ClientException $exception, string $url) : void
    {
        match (true) {
            $exception->getCode() === 401 => throw PlexAuthenticationError::create(),
            $exception->getCode() === 404 => throw PlexNotFoundError::create($url),

            default => throw new RuntimeException('Plex API error. Response message: ' . $exception->getMessage()),
        };
    }
}
