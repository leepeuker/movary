<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use Movary\Api\Plex\Exception\PlexAuthenticationInvalid;
use Movary\Api\Plex\Exception\PlexNotFoundError;
use Movary\Service\ServerSettings;
use Movary\Util\Json;
use Movary\ValueObject\Url;

abstract class PlexClient
{
    protected const array DEFAULT_HEADERS = [
        'accept' => 'application/json'
    ];

    public function __construct(
        protected readonly HttpClient $httpClient,
        protected readonly ServerSettings $serverSettings,
    ) {
    }

    protected function convertException(Exception $e, Url $requestUrl) : Exception
    {
        return match (true) {
            $e->getCode() === 401 => PlexAuthenticationInvalid::create(),
            $e->getCode() === 404 => PlexNotFoundError::create($requestUrl),
            default => $e
        };
    }

    protected function sendRequest(string $requestMethod, Url $requestUrl, array $requestOptions) : array
    {
        try {
            $response = $this->httpClient->request($requestMethod, (string)$requestUrl, $requestOptions);
        } catch (ClientException $e) {
            throw $this->convertException($e, $requestUrl);
        }

        /** @psalm-suppress PossiblyUndefinedVariable */
        return Json::decode((string)$response->getBody());
    }
}
