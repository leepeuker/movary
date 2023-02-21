<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use Movary\Api\Plex\Exception\PlexAuthenticationError;
use Movary\Util\Json;
use GuzzleHttp\Client as httpClient;
use GuzzleHttp\Exception\ClientException;
use Movary\Api\Plex\Exception\PlexNoClientIdentifier;
use Movary\Api\Plex\Exception\PlexNotFoundError;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PlexLocalServerClient
{
    private const APP_NAME = 'Movary';
    private const DEFAULTPOSTANDGETHEADERS = [
        'accept' => 'application/json',
        'Content-Type' => 'application/json'
    ];
    private array $defaultPostAndGetData;

    public function __construct(
        private readonly httpClient $httpClient,
        private readonly string $plexIdentifier,
        private readonly string $application_version,
        private readonly string $plexServerUrl
    ) {
        $this->defaultPostAndGetData = [
            'X-Plex-Client-Identifier' => $this->plexIdentifier,
            'X-Plex-Product' => self::APP_NAME,
            'X-Plex-Product-Version' => $this->application_version,
            'X-Plex-Platform' => php_uname('s'),
            'X-Plex-Platform-Version' => php_uname('v'),
            'X-Plex-Provides' => 'Controller',
            'strong' => 'true'
        ];
    }

    /**
     * @throws PlexNotFoundError
     * @throws PlexAuthenticationError
     * @throws PlexNoClientIdentifier
     * @throws RuntimeException
     */
    public function sendGetRequest(string $relativeUrl, ?array $customGetQuery = [], array $customGetData = [], array $customGetHeaders = [], ?string $customBaseUrl = null) : Array
    {
        if ($this->plexIdentifier === '') {
            throw PlexNoClientIdentifier::create();
        }
        $baseUrl = $customBaseUrl ?? $this->plexServerUrl;
        $url = $baseUrl . $relativeUrl;
        $data = array_merge($this->defaultPostAndGetData, $customGetData);
        $httpHeaders = array_merge(self::DEFAULTPOSTANDGETHEADERS, $customGetHeaders);
        $options = [
            'form_params' => $data,
            'query' => $customGetQuery,
            'headers' => $httpHeaders
        ];
        try {
            $response = $this->httpClient->request('GET', $url, $options);
            return Json::decode((string)$response->getBody());
        } catch (ClientException $e) {
            match(true) {
                $e->getCode() === 401 => throw PlexAuthenticationError::create(),
                $e->getCode() === 404 => throw PlexNotFoundError::create($url),
                default => throw new RuntimeException('Plex API error. Response message: '. $e->getMessage()),
            };
        }
    }

    /**
     * @throws PlexNotFoundError
     * @throws PlexAuthenticationError
     * @throws PlexNoClientIdentifier
     * @throws RuntimeException
     */
    public function sendPostRequest(string $relativeUrl, array $customPostData = [], array $customPostHeaders = [], string $customBaseUrl = null) : Array
    {
        if ($this->plexIdentifier === '') {
            throw PlexNoClientIdentifier::create();
        }
        $baseUrl = $customBaseUrl ?? $this->plexServerUrl;
        $url = $baseUrl . $relativeUrl;
        $postData = array_merge($this->defaultPostAndGetData, $customPostData);
        $httpHeaders = array_merge(self::DEFAULTPOSTANDGETHEADERS, $customPostHeaders);
        $options = [
            'form_params' => $postData,
            'headers' => $httpHeaders
        ];
        try {
            $response = $this->httpClient->request('POST', $url, $options);
            return Json::decode((string)$response->getBody());
        } catch (ClientException $e) {
            match(true) {
                $e->getCode() === 401 => throw PlexAuthenticationError::create(),
                $e->getCode() === 404 => throw PlexNotFoundError::create($url),
                default => throw new RuntimeException('Plex API error. Response message: '. $e->getMessage()),
            };
        }
    }
}