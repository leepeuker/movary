<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use Movary\Api\Plex\Exception\PlexAuthenticationError;
use Movary\Util\Json;
use Movary\ValueObject\Config;
use GuzzleHttp\Client as httpClient;
use GuzzleHttp\Exception\GuzzleException;
use Movary\Api\Plex\Exception\PlexNotFoundError;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PlexAuthenticationClient
{
    private const BASE_URL = "https://plex.tv/api/v2";
    private const APP_NAME = 'Movary';
    private $defaultPostAndGetData;
    private const DEFAULTPOSTANDGETHEADERS = [
        'accept' => 'application/json'
    ];

    public function __construct(private readonly httpClient $httpClient, private readonly Config $config, private readonly LoggerInterface $logger)
    {
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
     * @throws RuntimeException
     */
    public function sendGetRequest(string $relativeUrl, ?array $customGetData = [], ?array $customGetHeaders = []) : ?Array
    {
        if ($this->config->getAsString('PLEX_IDENTIFIER', '') === '') {
            return [];
        }
        $url = self::BASE_URL . $relativeUrl;
        $data = array_merge($this->defaultPostAndGetData, $customGetData);
        $httpHeaders = array_merge(self::DEFAULTPOSTANDGETHEADERS, $customGetHeaders);
        $options = [
            'form_params' => $data,
            'headers' => $httpHeaders
        ];
        try {
            $response = $this->httpClient->request('get', $url, $options);
            $statusCode = $response->getStatusCode();
            match(true) {
                $statusCode === 200 || $statusCode === 201 || $statusCode || 204 => true,
                $statusCode === 401 => throw PlexAuthenticationError::create(),
                $statusCode === 404 => throw PlexNotFoundError::create($url),
                default => throw new RuntimeException('Plex API error. Response status code: '. $statusCode),
            };
            return Json::decode((string)$response->getBody());
        } catch (GuzzleException $e) {
            $this->logger->error("The following error occured while sending a HTTP request to the Plex authentication server: " . $e->getMessage());
            return null;
        }
    }

    /**
     * @throws PlexNotFoundError
     * @throws PlexAuthenticationError
     */
    public function sendPostRequest(string $relativeUrl, ?array $customPostData = [], ?array $customPostHeaders = []) : ?Array
    {
        if ($this->config->getAsString('PLEX_IDENTIFIER', '') === '') {
            return [];
        }
        $url = self::BASE_URL . $relativeUrl;
        $postData = array_merge($this->defaultPostAndGetData, $customPostData);
        $httpHeaders = array_merge(self::DEFAULTPOSTANDGETHEADERS, $customPostHeaders);
        $options = [
            'form_params' => $postData,
            'headers' => $httpHeaders
        ];
        try {
            $response = $this->httpClient->request('post', $url, $options);
            $statusCode = $response->getStatusCode();
            match(true) {
                $statusCode === 200 || $statusCode === 201 || $statusCode || 204 => true,
                $statusCode === 401 => throw PlexAuthenticationError::create(),
                $statusCode === 404 => throw PlexNotFoundError::create($url),
                default => throw new RuntimeException('Plex API error. Response status code: '. $statusCode),
            };
            return Json::decode((string)$response->getBody());
        } catch (GuzzleException $e) {
            $this->logger->error("The following error occured while sending a HTTP request to the Plex authentication server: " . $e->getMessage());
            return null;
        }
    }
}