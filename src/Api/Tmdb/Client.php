<?php declare(strict_types=1);

namespace Movary\Api\Tmdb;

use GuzzleHttp\Psr7\Request;
use Movary\Util\Json;
use Psr\Http\Client\ClientInterface;

class Client
{
    private const BASE_URL = 'https://api.themoviedb.org/3';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly string $apiKey
    ) {
    }

    public function get(string $relativeUrl, array $getParameters = []) : array
    {
        $getParametersRendered = '?';

        foreach ($getParameters as $name => $getParameter) {
            $getParametersRendered .= $name . '=' . $getParameter . '&';
        }

        $getParametersRendered .= 'api_key=' . $this->apiKey;

        $request = new Request(
            'GET',
            self::BASE_URL . $relativeUrl . $getParametersRendered,
        );

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Api error. Response status code: ' . $response->getStatusCode());
        }

        return Json::decode((string)$response->getBody());
    }
}
