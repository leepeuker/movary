<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use Movary\Api\Plex\Exception\PlexAuthenticationError;
use Movary\Util\Json;
use Movary\ValueObject\Config;
use GuzzleHttp\Client as httpClient;
use GuzzleHttp\Exception\ClientException;
use Movary\Api\Plex\Exception\PlexNoClientIdentifier;
use Movary\Api\Plex\Exception\PlexNotFoundError;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PlexTvClient
{
    private const BASE_URL = "https://plex.tv/api/v2";
    private const APP_NAME = 'Movary';
    private array $defaultPostAndGetData;
    private const DEFAULTPOSTANDGETHEADERS = [
        'accept' => 'application/json'
    ];

    public function __construct(
        private readonly httpClient $httpClient,
        private readonly Config $config
    ) {
        $this->defaultPostAndGetData = [
            'X-Plex-Client-Identifier' => $this->config->getAsString('PLEX_IDENTIFIER'),
            'X-Plex-Product' => self::APP_NAME,
            'X-Plex-Product-Version' => $this->config->getAsString('APPLICATION_VERSION'),
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
     * @psalm-suppress InvalidReturnType
     */
    public function sendGetRequest(string $relativeUrl, array $customGetData = [], array $customGetHeaders = [], ?string $customBaseUrl = null) : array
    {
        if ($this->config->getAsString('PLEX_IDENTIFIER', '') === '') {
            throw PlexNoClientIdentifier::create();
        }
        $baseurl = $customBaseUrl ?? self::BASE_URL;
        $url = $baseurl . $relativeUrl;
        $data = array_merge($this->defaultPostAndGetData, $customGetData);
        $httpHeaders = array_merge(self::DEFAULTPOSTANDGETHEADERS, $customGetHeaders);
        $options = [
            'form_params' => $data,
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
     * @psalm-suppress InvalidReturnType
     */
    public function sendPostRequest(string $relativeUrl, array $customPostData = [], array $customPostHeaders = []) : array
    {
        if ($this->config->getAsString('PLEX_IDENTIFIER', '') === '') {
            throw PlexNoClientIdentifier::create();
        }
        $url = self::BASE_URL . $relativeUrl;
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