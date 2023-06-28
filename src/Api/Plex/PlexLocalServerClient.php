<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use Movary\Api\Plex\Exception\PlexAuthenticationError;
use Movary\Api\Plex\Exception\PlexNoClientIdentifier;
use Movary\Api\Plex\Exception\PlexNotFoundError;
use Movary\Util\Json;
use RuntimeException;

class PlexLocalServerClient
{
    private const APP_NAME = 'Movary';

    private const DEFAULT_HEADERS = [
        'accept' => 'application/json',
        'Content-Type' => 'application/json'
    ];

    private array $defaultPostAndGetData;

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly string $plexIdentifier,
        private readonly string $applicationVersion,
    ) {
        $this->defaultPostAndGetData = [
            'X-Plex-Client-Identifier' => $this->plexIdentifier,
            'X-Plex-Product' => self::APP_NAME,
            'X-Plex-Product-Version' => $this->applicationVersion,
            'X-Plex-Platform' => php_uname('s'),
            'X-Plex-Platform-Version' => php_uname('v'),
            'X-Plex-Provides' => 'Controller',
            'strong' => 'true'
        ];
    }

    public function sendGetRequest(
        string $userId,
        ?array $customGetQuery = [],
    ) : array {
        if ($this->plexIdentifier === '') {
            throw PlexNoClientIdentifier::create();
        }

        $requestUrl = $baseUrl . $relativeUrl;
        $requestOptions = [
            'form_params' => $this->defaultPostAndGetData,
            'query' => $customGetQuery,
            'headers' => self::DEFAULT_HEADERS
        ];

        try {
            $response = $this->httpClient->request('GET', $requestUrl, $requestOptions);
        } catch (ClientException $e) {
            match (true) {
                $e->getCode() === 401 => throw PlexAuthenticationError::create(),
                $e->getCode() === 404 => throw PlexNotFoundError::create($requestUrl),
                default => throw new RuntimeException('Plex API error. Response message: ' . $e->getMessage()),
            };
        }

        return Json::decode((string)$response->getBody());
    }
}
