<?php declare(strict_types=1);

namespace Movary\Api\Trakt;

use GuzzleHttp\Psr7\Request;
use Movary\Util\Json;
use Psr\Http\Client\ClientInterface;

class Client
{
    private const BASE_URL = 'https://api.trakt.tv';

    private const TRAKT_API_VERSION = '2';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $clientId
    ) {
    }

    public function get(string $relativeUrl) : array
    {
        $request = new Request(
            'GET',
            self::BASE_URL . $relativeUrl,
            [
                'Content-Type' => 'application/json',
                'trakt-api-version' => self::TRAKT_API_VERSION,
                'trakt-api-key' => $this->clientId,
            ]
        );

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Api error. Response status code: ' . $response->getStatusCode());
        }

        return Json::decode((string)$response->getBody());
    }
}
