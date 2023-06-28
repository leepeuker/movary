<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use Movary\Api\Plex\Exception\PlexAuthenticationError;
use Movary\Api\Plex\Exception\PlexNoClientIdentifier;
use Movary\Api\Plex\Exception\PlexNotFoundError;
use Movary\Util\Json;
use Movary\ValueObject\RelativeUrl;
use Movary\ValueObject\Url;
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

    /**
     * @psalm-suppress PossiblyUndefinedVariable
     */
    public function get(
        RelativeUrl $relativeUrl,
        array $headers = [],
    ) : array {
        if ($this->plexIdentifier === null) {
            throw PlexNoClientIdentifier::create();
        }

        $requestUrl = Url::createFromString(self::BASE_URL)->appendRelativeUrl($relativeUrl);
        $requestOptions = [
            'form_params' => $this->defaultFormData,
            'headers' => array_merge(self::DEFAULT_HEADERS, $headers)
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

    /**
     * @psalm-suppress PossiblyUndefinedVariable
     */
    public function sendPostRequest(RelativeUrl $relativeUrl) : array
    {
        if ($this->plexIdentifier === null) {
            throw PlexNoClientIdentifier::create();
        }

        $requestUrl = Url::createFromString(self::BASE_URL)->appendRelativeUrl($relativeUrl);
        $requestOptions = [
            'form_params' => $this->defaultFormData,
            'headers' => self::DEFAULT_HEADERS
        ];

        try {
            $response = $this->httpClient->request('POST', (string)$requestUrl, $requestOptions);
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
