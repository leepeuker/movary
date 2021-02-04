<?php declare(strict_types=1);

namespace Movary\Api\Tmdb;

use GuzzleHttp\Psr7\Request;
use Movary\Util\Json;
use Psr\Http\Client\ClientInterface;

class Client
{
    private const BASE_URL = 'https://api.themoviedb.org/3';

    private string $apiKey;

    private ClientInterface $httpClient;

    public function __construct(ClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function get(string $relativeUrl) : array
    {
        $request = new Request(
            'GET',
            self::BASE_URL . $relativeUrl . '?api_key=' . $this->apiKey,
        );

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Api error. Response status code: ' . $response->getStatusCode());
        }

        return Json::decode((string)$response->getBody());
    }
}
