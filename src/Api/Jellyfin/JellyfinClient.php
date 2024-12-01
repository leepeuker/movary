<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin;

use Exception;
use Generator;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Movary\Api\Jellyfin\Dto\JellyfinAccessToken;
use Movary\Api\Jellyfin\Exception\JellyfinInvalidAuthentication;
use Movary\Api\Jellyfin\Exception\JellyfinNotFoundError;
use Movary\Api\Jellyfin\Exception\JellyfinServerConnectionError;
use Movary\Service\ServerSettings;
use Movary\Util\Json;
use Movary\ValueObject\Url;
use RuntimeException;

class JellyfinClient
{
    private const int DEFAULT_PAGINATION_LIMIT = 500;

    private const int DEFAULT_TIMEOUT = 4;

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly ServerSettings $serverSettings,
    ) {
    }

    public function delete(Url $jellyfinServerUrl, ?array $query = [], ?JellyfinAccessToken $jellyfinAccessToken = null) : void
    {
        $options = [
            'query' => $query,
            'headers' => $this->generateHeaders($jellyfinAccessToken)
        ];

        try {
            $this->httpClient->request('DELETE', (string)$jellyfinServerUrl, $options);
        } catch (ClientException $e) {
            throw $this->convertException($e, $jellyfinServerUrl);
        }
    }

    public function get(Url $jellyfinServerUrl, ?array $query = [], ?JellyfinAccessToken $jellyfinAccessToken = null, ?int $timeout = null) : ?array
    {
        $options = [
            'query' => $query,
            'headers' => $this->generateHeaders($jellyfinAccessToken),
            'connect_timeout' => $timeout ?? self::DEFAULT_TIMEOUT,
        ];

        try {
            $response = $this->httpClient->request('GET', (string)$jellyfinServerUrl, $options);
        } catch (ClientException $e) {
            throw $this->convertException($e, $jellyfinServerUrl);
        }

        return Json::decode((string)$response->getBody());
    }

    public function getPaginated(Url $jellyfinServerUrl, ?array $query = [], ?JellyfinAccessToken $jellyfinAccessToken = null, ?int $timeout = null) : Generator
    {
        yield $response = $this->get($jellyfinServerUrl, $query, $jellyfinAccessToken, $timeout);

        $limit = $query['limit'] ?? self::DEFAULT_PAGINATION_LIMIT;
        $totalMoviesCount = $response['TotalRecordCount'] ?? null;

        if ($totalMoviesCount === null) {
            throw new RuntimeException('Could not extract total record count from response');
        }

        $pages = ceil($totalMoviesCount / $limit);

        for ($i = 2; $i <= $pages; $i++) {
            $query['StartIndex'] = $limit * ($i - 1);

            yield $this->get($jellyfinServerUrl, $query, $jellyfinAccessToken, $timeout);
        }
    }

    public function post(Url $jellyfinServerUrl, ?array $query = [], ?array $data = [], ?JellyfinAccessToken $jellyfinAccessToken = null) : ?array
    {
        $options = [
            'json' => $data,
            'query' => $query,
            'headers' => $this->generateHeaders($jellyfinAccessToken)
        ];

        try {
            $response = $this->httpClient->request('POST', (string)$jellyfinServerUrl, $options);
        } catch (ClientException $e) {
            throw $this->convertException($e, $jellyfinServerUrl);
        } catch (ConnectException) {
            throw JellyfinServerConnectionError::create($jellyfinServerUrl);
        }

        /** @psalm-suppress PossiblyUndefinedVariable */
        return Json::decode((string)$response->getBody());
    }

    private function convertException(Exception $e, Url $url) : Exception
    {
        return match (true) {
            $e->getCode() === 401 || $e->getCode() === 400 => JellyfinInvalidAuthentication::create(),
            $e->getCode() === 404 => JellyfinNotFoundError::create($url),
            $e->getCode() !== 200 => throw new RuntimeException('Api error. Response status code: ' . $e->getCode()),
            default => $e
        };
    }

    private function generateHeaders(?JellyfinAccessToken $jellyfinAccessToken = null) : array
    {
        $appName = $this->serverSettings->getJellyfinAppName();
        $appVersion = $this->serverSettings->getApplicationVersion();
        $deviceId = $this->serverSettings->requireJellyfinDeviceId();

        $authorizationString = 'MediaBrowser Client ="' . $appName . '", Device ="' . php_uname('s') . '", Version="' . $appVersion . '", DeviceId="' . $deviceId . '"';
        if ($jellyfinAccessToken !== null) {
            $authorizationString .= ', Token="' . $jellyfinAccessToken . '"';
        }

        return [
            'X-Emby-Authorization' => $authorizationString,
            'Accept' => 'application/json',
        ];
    }
}
