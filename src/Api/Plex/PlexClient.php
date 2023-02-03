<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use GuzzleHttp\Psr7\Request;
use Movary\Api\Plex\Exception\PlexAuthenticationError;
use Movary\Util\Json;
use Movary\ValueObject\Config;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

class PlexClient
{
    private const BASE_URL = "https://plex.tv/api/v2";
    private const APP_NAME = 'Movary';
    private $defaultHeaders;

    public function __construct(private readonly ClientInterface $httpClient, private readonly Config $config)
    {
        $this->defaultHeaders = [
            'X-Plex-Client-Identifier' => $this->config->getAsString('PLEX_IDENTIFIER'),
            'X-Plex-Product' => self::APP_NAME,
            'X-Plex-Product-Version' => $this->config->getAsString('APPLICATION_VERSION'),
            'X-Plex-Platform' => php_uname('s'),
            'X-Plex-Platform-Version' => php_uname('v'),
            'X-Plex-Provides' => 'Controller',
            'strong' => 'true',
            'Accept' => 'application/json'
        ];
    }


    public function sendGetRequest(string $relativeUrl, ?array $customHeaders = [] ) : Array
    {
        if ($this->config->getAsString('PLEX_IDENTIFIER', '') === '') {
            return [];
        }
        $url = self::BASE_URL . $relativeUrl;
        $headers = array_merge($this->defaultHeaders, $customHeaders);
        $request = new Request('get', $url, $headers);
        $response = $this->httpClient->sendRequest($request);
        $statusCode = $response->getStatusCode();
        match(true) {
            $statusCode === 401 => throw PlexAuthenticationError::create(),
            $statusCode !== 200 && $statusCode !== 201 => throw new RuntimeException('Plex API error. Response status code: '. $statusCode),
            default => true
        };
        return Json::decode((string)$response->getBody());
    }

    public function sendPostRequest(string $relativeUrl, ?array $customHeaders = []) : Array
    {
        if ($this->config->getAsString('PLEX_IDENTIFIER', '') === '') {
            return [];
        }
        $url = self::BASE_URL . $relativeUrl;
        $headers = array_merge($this->defaultHeaders, $customHeaders);
        $request = new Request('post', $url, $headers);
        $response = $this->httpClient->sendRequest($request);
        $statusCode = $response->getStatusCode();
        match(true) {            
            $statusCode === 401 => throw PlexAuthenticationError::create(),
            $statusCode !== 200 && $statusCode !== 201 => throw new RuntimeException('Plex API error. Response status code: '. $statusCode),
            default => true
        };

        return Json::decode((string)$response->getBody());
    }
}