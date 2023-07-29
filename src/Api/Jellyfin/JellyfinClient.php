<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use Movary\Api\Jellyfin\Dto\JellyfinAccessToken;
use Movary\Api\Jellyfin\Exception\JellyfinInvalidAuthentication;
use Movary\Api\Jellyfin\Exception\JellyfinInvalidServerUrl;
use Movary\Api\Jellyfin\Exception\JellyfinNotFoundError;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\ServerSettings;
use Movary\Util\Json;
use Movary\ValueObject\Url;
use RuntimeException;

class JellyfinClient
{
    private const APP_NAME = 'Movary';

    private const DEFAULTHEADERS = [
        'Accept' => 'application/json',
    ];

    private array $authorizationString;

    private ?JellyfinAccessToken $jellyfinAccessToken;

    private ?Url $jellyfinServerUrl;

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly ServerSettings $serverSettings,
    ) {
        $this->jellyfinAccessToken = $this->userApi->findJellyfinAccessToken($this->authenticationService->getCurrentUserId());
        $this->jellyfinServerUrl = $this->userApi->findJellyfinServerUrl($this->authenticationService->getCurrentUserId());
        $this->authorizationString = ['X-Emby-Authorization' => 'MediaBrowser Client ="' . self::APP_NAME . '", Device ="' . php_uname('s') . '", Version="' . $this->serverSettings->getApplicationVersion() . '", DeviceId="' . $this->serverSettings->getJellyfinDeviceId() . '"'];
        if ($this->jellyfinAccessToken !== null) {
            $this->authorizationString['X-Emby-Authorization'] .= ', Token="' . $this->jellyfinAccessToken . '"';
        }
    }

    public function delete(string $relativeUrl, ?array $query = []) : ?string
    {
        if ($this->jellyfinServerUrl === null) {
            return null;
        }

        $headers = array_merge(self::DEFAULTHEADERS, $this->authorizationString);

        $options = [
            'headers' => $headers,
            'query' => $query
        ];

        $url = $this->jellyfinServerUrl . $relativeUrl;

        try {
            $response = $this->httpClient->request('DELETE', $url, $options);
        } catch (ClientException $e) {
            throw $this->convertException($e, Url::createFromString($url));
        }

        /** @psalm-suppress PossiblyUndefinedVariable */
        return (string)$response->getBody();
    }

    public function get(string $relativeUrl, ?array $query = []) : ?array
    {
        if ($this->jellyfinServerUrl === null) {
            JellyfinInvalidServerUrl::create();

            return null;
        }

        $headers = array_merge(self::DEFAULTHEADERS, $this->authorizationString);

        $options = [
            'query' => $query,
            'headers' => $headers
        ];

        $url = $this->jellyfinServerUrl . $relativeUrl;

        $response = $this->httpClient->request('GET', $url, $options);

        $statusCode = $response->getStatusCode();

        match (true) {
            $statusCode === 401 => JellyfinInvalidAuthentication::create(),
            $statusCode === 404 => JellyfinNotFoundError::create(Url::createFromString($url)),
            $statusCode !== 200 => throw new RuntimeException('Api error. Response status code: ' . $statusCode),
            default => true
        };

        return Json::decode((string)$response->getBody());
    }

    public function post(string $relativeUrl, ?array $query = [], ?array $data = []) : ?array
    {
        if ($this->jellyfinServerUrl === null) {
            JellyfinInvalidServerUrl::create();

            return null;
        }

        $headers = array_merge(self::DEFAULTHEADERS, $this->authorizationString);

        $options = [
            'json' => $data,
            'query' => $query,
            'headers' => $headers
        ];

        $url = $this->jellyfinServerUrl . $relativeUrl;

        try {
            $response = $this->httpClient->request('POST', $url, $options);
        } catch (ClientException $e) {
            throw $this->convertException($e, Url::createFromString($url));
        }

        /** @psalm-suppress PossiblyUndefinedVariable */
        return Json::decode((string)$response->getBody());
    }

    private function convertException(\Exception $e, Url $url) : \Exception
    {
        return match (true) {
            $e->getCode() === 401 || $e->getCode() === 400 => JellyfinInvalidAuthentication::create(),
            $e->getCode() === 404 => JellyfinNotFoundError::create($url),
            $e->getCode() !== 200 => throw new RuntimeException('Api error. Response status code: ' . $e->getCode()),
            default => $e
        };
    }
}