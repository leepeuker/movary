<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use GuzzleHttp\Psr7\Request;
use Movary\Api\Plex\Exception\PlexAuthorizationError;
use Movary\Util\Json;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

class PlexTvClient
{
    private const BASE_URL = 'https://metadata.provider.plex.tv/';

    public function __construct(
        private readonly ClientInterface $httpClient,
    ) {
    }

    public function get(string $relativeUrl, int $limit = 20, int $offset = 0) : array
    {
        $request = new Request('GET', self::BASE_URL . ltrim($relativeUrl, '/'), [
            'Content-Type' => 'application/json',
            'X-Plex-Container-Size' => $limit,
            'X-Plex-Container-Start' => $offset,
            'Accept' => 'application/json'
        ]);

        $response = $this->httpClient->sendRequest($request);

        $statusCode = $response->getStatusCode();

        match (true) {
            $statusCode === 401 => throw PlexAuthorizationError::create(),
            $statusCode !== 200 => throw new RuntimeException('Api error. Response status code: ' . $statusCode),
            default => true
        };

        return Json::decode((string)$response->getBody());
    }
}
